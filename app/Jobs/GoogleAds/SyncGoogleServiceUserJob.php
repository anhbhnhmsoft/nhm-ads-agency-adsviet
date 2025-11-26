<?php

namespace App\Jobs\GoogleAds;

use App\Models\ServiceUser;
use App\Service\GoogleAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGoogleServiceUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ServiceUser $serviceUser,
    ) {
        $this->onQueue('meta-api');
    }

    public function handle(GoogleAdsService $googleAdsService): void
    {
        $googleAdsService->syncGoogleAccounts($this->serviceUser);
        $googleAdsService->syncGoogleCampaigns($this->serviceUser);
        $googleAdsService->syncGoogleInsights($this->serviceUser);
    }
}

