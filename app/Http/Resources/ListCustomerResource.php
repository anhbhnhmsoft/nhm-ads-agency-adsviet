<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListCustomerResource extends JsonResource
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
            'role' => $this->role,
            'disabled' => $this->disabled,
            'using_telegram' => !empty($this->telegram_id),
            'using_whatsapp' => !empty($this->whatsapp_id),
            'referral_code' => $this->referral_code,
        ];
    }
}
