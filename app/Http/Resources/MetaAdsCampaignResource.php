<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaAdsCampaignResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        /**
         * "id": "66426551464363330",
         * "service_user_id": "66158753519502712",
         * "meta_account_id": "66426444752881300",
         * "campaign_id": "23851264669920798",
         * "name": "Facebook Ac For Rent",
         * "status": "PAUSED",
         * "effective_status": "PAUSED",
         * "objective": "OUTCOME_LEADS",
         * "daily_budget": "1000000",
         * "budget_remaining": "1000000",
         * "created_time": "2025-10-25 16:40:14",
         * "start_time": "2025-10-25 16:40:15",
         * "stop_time": null,
         * "last_synced_at": "2025-11-17 03:14:56",
         * "deleted_at": null,
         * "created_at": "2025-11-17T03:14:56.000000Z",
         * "updated_at": "2025-11-17T03:14:56.000000Z"
         */
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
            'created_time' => $this->created_time,
            'start_time' => $this->start_time,
            'stop_time' => $this->stop_time,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
