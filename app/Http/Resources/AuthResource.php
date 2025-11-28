<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'disabled' => $this->disabled,
            'referral_code' => $this->referral_code,
            'role' => $this->role,
            'is_verified_email' => !empty($this->email_verified_at),
            'is_verified_telegram' => !empty($this->telegram_id),
        ];
    }
}
