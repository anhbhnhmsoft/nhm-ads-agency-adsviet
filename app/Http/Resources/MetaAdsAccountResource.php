<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaAdsAccountResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'account_id' => $this->account_id,
            'account_name' => $this->account_name,
            'account_status' => $this->account_status,
            'spend_cap' => $this->spend_cap,
            'amount_spent' => $this->amount_spent,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'is_prepay_account' => $this->is_prepay_account,
            'timezone_id' => $this->timezone_id,
            'timezone_name' => $this->timezone_name,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
