<?php

namespace App\Jobs\GoogleAds;

use App\Common\Constants\QueueKey\QueueKey;
use App\Service\GoogleAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * DDồng bộ dữ liệu từ Platform Settings Google Ads (MCC + Accounts)
 * Chayj khi admin lưu Platform Settings Google Ads
 */
class SyncGooglePlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $managerId,
    ) {
        $this->onQueue(QueueKey::GOOGLE_API);
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAdsService $googleAdsService): void
    {
        $googleAdsService->syncFromManagerId($this->managerId);
    }
}



