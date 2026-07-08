<?php

namespace App\Jobs\MetaApi;

use App\Common\Constants\QueueKey\QueueKey;
use App\Service\MetaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Cache;
use App\Core\Logging;

/**
 * Đồng bộ dữ liệu từ Platform Settings Meta (BM + Accounts)
 * Chay khi admin lưu Platform Settings Meta
 */
class SyncMetaPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(
        protected ?string $bmId = null,
        protected ?string $settingId = null,
    ) {
        $this->onQueue(QueueKey::META_API);
    }

    public function handle(MetaService $metaService): void
    {
        if (Cache::has('meta_api_rate_limited_cooldown')) {
            Logging::web("SyncMetaPlatformJob: Đang trong thời gian cooldown rate limit Meta. Bỏ qua.");
            return;
        }

        if ($this->bmId) {
            $metaService->syncFromBusinessManagerId($this->bmId, $this->settingId);
            return;
        }

        $metaService->syncFromAccessibleBusinessManagers($this->settingId);
    }
}

