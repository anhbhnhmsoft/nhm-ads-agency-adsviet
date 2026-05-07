<?php

namespace App\Jobs\MetaApi;

use App\Common\Constants\QueueKey\QueueKey;
use App\Service\MetaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Đồng bộ dữ liệu từ Platform Settings Meta (BM + Accounts)
 * Chay khi admin lưu Platform Settings Meta
 */
class SyncMetaPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?string $bmId = null,
        protected ?string $settingId = null,
    ) {
        $this->onQueue(QueueKey::META_API);
    }

    public function handle(MetaService $metaService): void
    {
        if ($this->bmId) {
            $metaService->syncFromBusinessManagerId($this->bmId, $this->settingId);
            return;
        }

        $metaService->syncFromAccessibleBusinessManagers($this->settingId);
    }
}


