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
        // Lấy người trực tiếp giới thiệu (owner)
        $referral = $this->referredBy?->referrer;
        $manager = null;

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
            'warning_threshold' => $this->warning_threshold,
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
