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
            'features' => $this->features, // Assuming features are already cast to array/json in model
            'monthly_spending_fee_structure' => $this->monthly_spending_fee_structure,
            'open_fee' => $this->open_fee,
            'range_min_top_up' => $this->range_min_top_up,
            'top_up_fee' => $this->top_up_fee,
            'supplier_fee_percent' => $this->supplier_fee_percent,
            'set_up_time' => $this->set_up_time,
            'disabled' => $this->disabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
