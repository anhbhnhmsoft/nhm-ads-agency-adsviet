<?php

namespace App\Http\Resources;

use App\Common\Constants\User\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListEmployeeResource extends JsonResource
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
            'username' => $this->username,
            'phone' => $this->phone,
            'referral_code' => $this->referral_code,
            'role' => $this->role,
            'disabled' => $this->disabled,
        ];
    }
}
