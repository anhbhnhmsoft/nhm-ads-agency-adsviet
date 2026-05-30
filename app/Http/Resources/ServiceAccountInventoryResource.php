<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceAccountInventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'service_package_id' => (string) $this->service_package_id,
            'platform' => $this->platform,
            'account_id' => $this->account_id,
            'account_name' => $this->account_name,
            'business_manager_id' => $this->business_manager_id,
            'customer_manager_id' => $this->customer_manager_id,
            'status' => $this->status,
            'assigned_user_id' => $this->assigned_user_id ? (string) $this->assigned_user_id : null,
            'assigned_service_user_id' => $this->assigned_service_user_id ? (string) $this->assigned_service_user_id : null,
            'link_target_type' => $this->link_target_type,
            'link_target_value' => $this->link_target_value,
            'metadata' => $this->metadata,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
