<?php

namespace App\Http\Resources;

use App\Common\Constants\Google\GoogleCustomerStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleAdsAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = GoogleCustomerStatus::fromValue($this->account_status);

        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'account_id' => $this->account_id,
            'account_name' => $this->account_name,
            'account_status' => $this->account_status,
            'status_label' => $status?->label(),
            'status_severity' => $status?->severity(),
            'status_message' => $status?->message(),
            'currency' => $this->currency,
            'customer_manager_id' => $this->customer_manager_id,
            'time_zone' => $this->time_zone,
            'primary_email' => $this->primary_email,
            'balance' => $this->balance,
            'balance_exhausted' => $this->balance_exhausted,
            'spending_limit' => $this->spending_limit,
            'total_spent' => $this->total_spent,
            'amount_spent' => $this->amount_spent,
            'remaining_amount' => $this->balance !== null ? max(0.0, (float) $this->balance) : ($this->spending_limit !== null ? max(0.0, (float) $this->spending_limit - (float) ($this->total_spent ?? 0)) : null),
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}

