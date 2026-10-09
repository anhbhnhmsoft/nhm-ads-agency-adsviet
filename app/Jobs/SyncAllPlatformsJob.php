<?php

namespace App\Jobs;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\QueueKey\QueueKey;
use App\Jobs\GoogleAds\SyncGooglePlatformJob;
use App\Jobs\MetaApi\SyncMetaPlatformJob;
use App\Service\PlatformSettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Core\Logging;

/**
 * Job tổng tư lệnh: Đồng bộ tất cả các Platform (Meta + Google) đang hoạt động
 */
class SyncAllPlatformsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public bool $syncInsights = true)
    {
        $this->onQueue(QueueKey::META_API);
    }

    /**
     * Execute the job.
     */
    public function handle(PlatformSettingService $platformSettingService): void
    {
        try {
            $typeStr = $this->syncInsights ? 'Full (Accounts + Insights)' : 'Accounts Only (Fast)';
            Logging::web("SyncAllPlatformsJob: Starting global synchronization [{$typeStr}]");

            // 1. Đồng bộ Meta
            if (\Illuminate\Support\Facades\Cache::has('meta_api_rate_limited_cooldown')) {
                Logging::web("SyncAllPlatformsJob: Bỏ qua đồng bộ Meta do đang trong thời gian cooldown rate limit");
            } else {
                $metaSettings = $platformSettingService->getAllActiveByPlatform(PlatformType::META->value);
                if ($metaSettings->isSuccess()) {
                    foreach ($metaSettings->getData() as $setting) {
                        $config = $setting->config ?? [];
                        $bmId = $platformSettingService->getMetaScopedBusinessManagerId($config);
                        SyncMetaPlatformJob::dispatch($bmId ? (string)$bmId : null, (string)$setting->id, $this->syncInsights);
                        Logging::web("SyncAllPlatformsJob: Dispatched Meta sync ({$typeStr}) for setting ID {$setting->id}");
                    }
                }
            }

            // 2. Đồng bộ Google
            $googleSettings = $platformSettingService->getAllActiveByPlatform(PlatformType::GOOGLE->value);
            if ($googleSettings->isSuccess()) {
                foreach ($googleSettings->getData() as $setting) {
                    $config = $setting->config ?? [];
                    $loginCustomerId = $config['login_customer_id'] ?? null;
                    if ($loginCustomerId) {
                        SyncGooglePlatformJob::dispatch((string)$loginCustomerId, (string)$setting->id);
                        Logging::web("SyncAllPlatformsJob: Dispatched Google sync for MCC {$loginCustomerId} (Setting ID: {$setting->id})");
                    }
                }
            }

            Logging::web("SyncAllPlatformsJob: Global synchronization dispatched successfully [{$typeStr}]");
        } catch (\Throwable $e) {
            Logging::error('SyncAllPlatformsJob error: ' . $e->getMessage(), exception: $e);
        }
    }
}
