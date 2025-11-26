<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleAdsAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'account_id' => $this->account_id,
            'account_name' => $this->account_name,
            'account_status' => $this->account_status,
            'currency' => $this->currency,
            'customer_manager_id' => $this->customer_manager_id,
            'time_zone' => $this->time_zone,
            'primary_email' => $this->primary_email,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}

