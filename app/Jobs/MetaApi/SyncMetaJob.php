<?php

namespace App\Jobs\MetaApi;

use App\Common\Constants\QueueKey\QueueKey;
use App\Models\ServiceUser;
use App\Service\MetaService;
use App\Service\MetaAdsNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job phục vụ đồng bộ giữ liệu của 1 BM từ Meta API
 */
class SyncMetaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ServiceUser $serviceUser,
    )
    {
        $this->onQueue(QueueKey::META_API);
    }

    /**
     * Execute the job.
     */
    public function handle(
        MetaService $metaService,
        MetaAdsNotificationService $metaAdsNotificationService,
    ): void
    {
        // Đồng bộ tài khoản quảng cáo
        $metaService->syncMetaAccounts($this->serviceUser);

        // Đồng bộ chiến dịch quảng cáo và insight của ads account
        $metaService->syncMetaAdsAndCampaigns($this->serviceUser);

        // Sau khi sync xong, kiểm tra và gửi thông báo low balance
        $metaAdsNotificationService->sendLowBalanceAlerts();
    }

}
