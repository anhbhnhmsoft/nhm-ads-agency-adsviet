<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'platform' => $this->platform,
            'payment_type' => $this->payment_type ?? 'prepay',
            'can_use_postpay' => (bool) ($this->can_use_postpay ?? (($this->payment_type ?? 'prepay') === 'postpay')),
            'features' => $this->features, // Assuming features are already cast to array/json in model
            'monthly_spending_fee_structure' => $this->monthly_spending_fee_structure,
            'open_fee' => $this->open_fee,
            'range_min_top_up' => $this->range_min_top_up,
            'top_up_fee' => $this->top_up_fee,
            'supplier_fee_percent' => $this->supplier_fee_percent,
            'supplier_id' => $this->supplier_id,
            'set_up_time' => $this->set_up_time,
            'disabled' => $this->disabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
