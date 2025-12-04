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
            'features' => $this->features,
            'monthly_spending_fee_structure' => $this->monthly_spending_fee_structure,
            'open_fee' => $this->open_fee,
            'top_up_fee' => $this->top_up_fee,
            'set_up_time' => $this->set_up_time,
            'range_min_top_up' => $this->range_min_top_up,
            'disabled' => $this->disabled
        ];
    }
}
