<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $employee = $this->whenLoaded('employee');
        $servicePackage = $this->whenLoaded('servicePackage');

        return [
            'id' => (string) $this->id,
            'employee_id' => (string) $this->employee_id,
            'employee' => $employee ? [
                'id' => (string) $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'email' => $employee->email,
            ] : null,
            'service_package_id' => (string) $this->service_package_id,
            'service_package' => $servicePackage ? [
                'id' => (string) $servicePackage->id,
                'name' => $servicePackage->name,
                'platform' => (int) $servicePackage->platform,
            ] : null,
            'type' => $this->type,
            'rate' => $this->rate,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
        ];
    }
}



