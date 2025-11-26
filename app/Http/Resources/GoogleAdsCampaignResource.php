<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleAdsCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'google_account_id' => $this->google_account_id,
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'status' => $this->status,
            'effective_status' => $this->effective_status,
            'objective' => $this->objective,
            'daily_budget' => $this->daily_budget,
            'budget_remaining' => $this->budget_remaining,
            'total_spend' => (string) ($this->total_spend ?? '0'),
            'today_spend' => (string) ($this->today_spend ?? '0'),
            'start_time' => $this->start_time?->toIso8601String(),
            'stop_time' => $this->stop_time?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}

