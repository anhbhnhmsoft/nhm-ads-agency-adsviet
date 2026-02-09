<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->whenLoaded('employee');
        $customer = $this->whenLoaded('customer');

        return [
            'id' => (string) $this->id,
            'employee_id' => (string) $this->employee_id,
            'employee' => $employee ? [
                'id' => (string) $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
            ] : null,
            'customer_id' => $this->customer_id ? (string) $this->customer_id : null,
            'customer' => $customer ? [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'username' => $customer->username,
            ] : null,
            'type' => $this->type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'base_amount' => $this->base_amount,
            'commission_rate' => $this->commission_rate,
            'commission_amount' => $this->commission_amount,
            'period' => $this->period,
            'is_paid' => (bool) $this->is_paid,
            'paid_at' => $this->paid_at?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}



