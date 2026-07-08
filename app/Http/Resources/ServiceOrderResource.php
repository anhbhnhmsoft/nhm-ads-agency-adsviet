<?php

namespace App\Http\Resources;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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

        $user = $this->relationLoaded('user') ? $this->user : null;
        $referral = $user?->referredBy?->referrer;

        // Tính tổng chi phí
        $totalCost   = 0.0;
        $config      = $this->config_account ?? [];
        $paymentType = strtolower($package?->payment_type ?? ($config['payment_type'] ?? 'prepay'));
        $topUpAmount = isset($config['top_up_amount']) ? (float) $config['top_up_amount'] : 0.0;
        $serviceFee  = 0.0;

        $platform = (int) ($package?->platform ?? 0);
        $resolvedAccountIds = [];
        $resolvedBmIds = [];

        if ($platform === PlatformType::META->value && $this->relationLoaded('metaAccount')) {
            /** @var Collection<int, mixed> $metaAccounts */
            $metaAccounts = $this->metaAccount;
            $resolvedAccountIds = $metaAccounts->pluck('account_id')
                ->filter(fn ($id) => filled($id))
                ->values()
                ->all();
            $resolvedBmIds = $metaAccounts->pluck('business_manager_id')
                ->filter(fn ($id) => filled($id))
                ->unique()
                ->values()
                ->all();
        } elseif ($platform === PlatformType::GOOGLE->value && $this->relationLoaded('googleAccounts')) {
            /** @var Collection<int, mixed> $googleAccounts */
            $googleAccounts = $this->googleAccounts;
            $resolvedAccountIds = $googleAccounts->pluck('account_id')
                ->filter(fn ($id) => filled($id))
                ->values()
                ->all();
            $resolvedBmIds = $googleAccounts->pluck('customer_manager_id')
                ->filter(fn ($id) => filled($id))
                ->unique()
                ->values()
                ->all();
        }

        if (empty($resolvedAccountIds)) {
            $resolvedAccountIds = collect($config['account_ids'] ?? [])
                ->whenEmpty(function (Collection $collection) use ($config) {
                    $accountId = $config['account_id'] ?? null;
                    return filled($accountId) ? collect([$accountId]) : $collection;
                })
                ->filter(fn ($id) => filled($id))
                ->values()
                ->all();
        }

        if (empty($resolvedBmIds)) {
            $resolvedBmIds = collect($config['bm_ids'] ?? [])
                ->whenEmpty(function (Collection $collection) use ($config) {
                    $candidate = $config['child_bm_id'] ?? $config['bm_id'] ?? null;
                    return filled($candidate) ? collect([$candidate]) : $collection;
                })
                ->filter(fn ($id) => filled($id))
                ->unique()
                ->values()
                ->all();
        }

        $normalizedConfig = is_array($config)
            ? array_merge($config, [
                'resolved_account_ids' => $resolvedAccountIds,
                'resolved_bm_ids' => $resolvedBmIds,
            ])
            : $config;

        $openFee          = (float) ($package?->open_fee ?? 0);
        $topUpFeePercent  = (float) ($package?->top_up_fee ?? 0);
        $spendingFeePercent = (float) ($package?->spending_fee ?? 0);
        $isPostpay        = $paymentType === 'postpay';

        $accountsCount = 1;
        if (isset($config['accounts']) && is_array($config['accounts']) && count($config['accounts']) > 0) {
            $accountsCount = count($config['accounts']);
        }

        // Phí mở tài khoản được thu upfront cho cả trả trước và trả sau.
        $openFeePayable = $openFee * $accountsCount;

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
                'payment_type' => $package?->payment_type ?? 'prepay',
                'billing_source' => $package?->billing_source ?? 'adviet_card',
                'top_up_fee' => $package?->top_up_fee,
                'spending_fee' => $spendingFeePercent,
                'platform_label' => $package ? PlatformType::tryFrom((int) $package->platform)?->label() : null,
            ],
            'user' => [
                'name' => $user?->name,
                'referrer' => $referral ? [
                    'name' => $referral->name,
                ] : null,
            ],
            'budget' => $this->budget,
            'open_fee' => $package?->open_fee,
            'top_up_fee' => $package?->top_up_fee,
            'spending_fee' => $spendingFeePercent,
            'total_cost' => $totalCost,
            'config_account' => $normalizedConfig,
            'description' => $this->description,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
