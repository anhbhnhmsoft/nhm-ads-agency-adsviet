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
        // Log để debug
        \App\Core\Logging::web('ListCustomerResource@toArray: Processing user', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'has_referredBy' => $this->relationLoaded('referredBy'),
            'referredBy_id' => $this->referredBy?->id,
            'referredBy_referrer_id' => $this->referredBy?->referrer_id,
            'referredBy_referrer_loaded' => $this->referredBy?->relationLoaded('referrer'),
        ]);

        // Lấy người trực tiếp giới thiệu (owner)
        $referral = $this->referredBy?->referrer;
        $manager = null;

        // Log referral data
        if ($referral) {
            \App\Core\Logging::web('ListCustomerResource@toArray: Referral found', [
                'referral_id' => $referral->id,
                'referral_username' => $referral->username,
                'referral_role' => $referral->role,
                'has_referredBy_on_referral' => $referral->relationLoaded('referredBy'),
            ]);
        } else {
            \App\Core\Logging::web('ListCustomerResource@toArray: No referral found', [
                'referredBy_exists' => $this->referredBy !== null,
                'referredBy_referrer_exists' => $this->referredBy?->referrer !== null,
            ]);
        }

        // Xác định manager dựa trên role của owner
        if ($referral) {
            if ((int) $referral->role === UserRole::EMPLOYEE->value) {
                // Nếu owner là EMPLOYEE, thì manager là người quản lý employee đó
                $manager = $referral->referredBy?->referrer;
            } elseif ((int) $referral->role === UserRole::MANAGER->value) {
                // Nếu owner là MANAGER, thì manager chính là owner
                $manager = $referral;
            }
            // Nếu owner là AGENCY hoặc CUSTOMER, thì không có manager (chỉ có owner)
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'telegram_id' => $this->telegram_id,
            'phone' => $this->phone,
            'role' => $this->role,
            'disabled' => $this->disabled,
            'using_telegram' => !empty($this->telegram_id),
            'email_verified_at' => $this->email_verified_at,
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
