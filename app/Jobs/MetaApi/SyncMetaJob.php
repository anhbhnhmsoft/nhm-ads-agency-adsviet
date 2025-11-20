<?php

namespace App\Jobs\MetaApi;

use App\Models\ServiceUser;
use App\Service\MetaService;
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
        $this->onQueue('meta-api');
    }

    /**
     * Execute the job.
     */
    public function handle(
        MetaService $metaService
    ): void
    {
        // Đồng bộ tài khoản quảng cáo
        $metaService->syncMetaAccounts($this->serviceUser);

        // Đồng bộ chiến dịch quảng cáo và insight của ads account
        $metaService->syncMetaAdsAndCampaigns($this->serviceUser);
    }

}
