<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaAdsCampaignResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'meta_account_id' => $this->meta_account_id,
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'status' => $this->status,
            'effective_status' => $this->effective_status,
            'objective' => $this->objective,
            'daily_budget' => $this->daily_budget,
            'budget_remaining' => $this->budget_remaining,
            'total_spend' => (string)$this->total_spend,
            'today_spend' => (string)$this->today_spend,
            'created_time' => $this->created_time,
            'start_time' => $this->start_time,
            'stop_time' => $this->stop_time,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
