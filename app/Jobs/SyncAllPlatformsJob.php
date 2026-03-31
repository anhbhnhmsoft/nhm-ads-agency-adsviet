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

    public function __construct()
    {
        $this->onQueue(QueueKey::META_API);
    }

    /**
     * Execute the job.
     */
    public function handle(PlatformSettingService $platformSettingService): void
    {
        try {
            Logging::web('SyncAllPlatformsJob: Starting global synchronization');

            // 1. Đồng bộ Meta
            $metaSettings = $platformSettingService->getAllActiveByPlatform(PlatformType::META->value);
            if ($metaSettings->isSuccess()) {
                foreach ($metaSettings->getData() as $setting) {
                    $config = $setting->config ?? [];
                    $bmId = $config['business_manager_id'] ?? null;
                    if ($bmId) {
                        SyncMetaPlatformJob::dispatch((string)$bmId, (string)$setting->id);
                        Logging::web("SyncAllPlatformsJob: Dispatched Meta sync for BM {$bmId} (Setting ID: {$setting->id})");
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

            Logging::web('SyncAllPlatformsJob: Global synchronization dispatched successfully');
        } catch (\Throwable $e) {
            Logging::error('SyncAllPlatformsJob error: ' . $e->getMessage(), exception: $e);
        }
    }
}
