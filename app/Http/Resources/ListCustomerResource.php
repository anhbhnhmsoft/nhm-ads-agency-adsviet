<?php

namespace App\Http\Resources;

use App\Common\Constants\User\UserRole;
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
        $referral = $this->referredBy?->referrer;
        $manager = null;

        if ($referral && (int) $referral->role === UserRole::EMPLOYEE->value) {
            $manager = $referral->referredBy?->referrer;
        } elseif ($referral && (int) $referral->role === UserRole::MANAGER->value) {
            $manager = $referral;
        }

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
            'wallet_status' => optional($this->wallet)->status,
            'wallet_balance' => optional($this->wallet)->balance,
            'owner' => $referral ? [
                'id' => $referral->id,
                'username' => $referral->username,
                'role' => $referral->role,
            ] : null,
            'manager' => $manager ? [
                'id' => $manager->id,
                'username' => $manager->username,
            ] : null,
        ];
    }
}
