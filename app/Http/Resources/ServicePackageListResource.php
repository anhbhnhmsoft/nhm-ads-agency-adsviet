<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePackageListResource extends JsonResource
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
            'billing_source' => $this->billing_source ?? 'adviet_card',
            'can_use_postpay' => (bool) ($this->can_use_postpay ?? (($this->payment_type ?? 'prepay') === 'postpay')),
            'features' => $this->features,
            'monthly_spending_fee_structure' => $this->monthly_spending_fee_structure,
            'open_fee' => $this->open_fee,
            'top_up_fee' => $this->top_up_fee,
            'spending_fee' => $this->spending_fee,
            'inventory_total_count' => (int) ($this->inventory_total_count ?? 0),
            'inventory_available_count' => (int) ($this->inventory_available_count ?? 0),
            'supplier_fee_percent' => $this->supplier_fee_percent,
            'supplier_id' => $this->supplier_id,
            'set_up_time' => $this->set_up_time,
            'range_min_top_up' => $this->range_min_top_up,
            'disabled' => $this->disabled
        ];
    }
}
