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

    public int $timeout = 1800;
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ServiceUser $serviceUser,
        protected bool $syncAccounts = true,
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
        if ($this->syncAccounts) {
            // Đồng bộ tài khoản quảng cáo (BM/Asset groups/Ad accounts list)
            $syncResult = $metaService->syncMetaAccounts($this->serviceUser);
            if ($syncResult->isError()) {
                return;
            }
        } else {
            // Nếu không sync accounts, chỉ thiết lập ngữ cảnh/token cho BM/ServiceUser này
            $metaService->setupSettingContextForServiceUser($this->serviceUser);
        }

        // Đồng bộ chiến dịch quảng cáo và insight của ads account
        $metaService->syncMetaAdsAndCampaigns($this->serviceUser);

        // Sau khi sync xong, kiểm tra và gửi thông báo low balance
        // $metaAdsNotificationService->sendLowBalanceAlerts();
    }

}
