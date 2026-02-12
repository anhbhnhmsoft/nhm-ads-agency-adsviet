<?php

namespace App\Http\Resources;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = ServiceUserStatus::tryFrom((int) $this->status);
        $package = $this->package;

        $user = $this->whenLoaded('user');
        $referral = $user?->referredBy?->referrer;

        // Tính tổng chi phí
        $totalCost   = 0.0;
        $config      = $this->config_account ?? [];
        $paymentType = strtolower($config['payment_type'] ?? 'prepay');
        $topUpAmount = isset($config['top_up_amount']) ? (float) $config['top_up_amount'] : 0.0;
        $serviceFee  = 0.0;

        $openFee          = (float) ($package?->open_fee ?? 0);
        $topUpFeePercent  = (float) ($package?->top_up_fee ?? 0);
        $isPostpay        = $paymentType === 'postpay';

        $accountsCount = 1;
        if (isset($config['accounts']) && is_array($config['accounts']) && count($config['accounts']) > 0) {
            $accountsCount = count($config['accounts']);
        }

        // Phí mở tài khoản thực tế = open_fee * số tài khoản (nếu trả trước)
        $openFeePayable = $isPostpay ? 0.0 : ($openFee * $accountsCount);

        if ($topUpAmount > 0) {
            $serviceFee = $topUpAmount * $topUpFeePercent / 100;
            $totalCost  = $openFeePayable + $topUpAmount + $serviceFee;
        } elseif (!$isPostpay) {
            $totalCost = $openFeePayable;
        }

        return [
            'id' => (string) $this->id,
            'status' => $this->status,
            'status_label' => $status?->name,
            'package' => [
                'id' => $package?->id,
                'name' => $package?->name,
                'platform' => $package?->platform,
                'platform_label' => $package ? PlatformType::tryFrom((int) $package->platform)?->label() : null,
            ],
            'user' => [
                'referrer' => $referral ? [
                    'name' => $referral->name,
                ] : null,
            ],
            'budget' => $this->budget,
            'open_fee' => $package?->open_fee,
            'top_up_fee' => $package?->top_up_fee,
            'total_cost' => $totalCost,
            'config_account' => $this->config_account,
            'description' => $this->description,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

