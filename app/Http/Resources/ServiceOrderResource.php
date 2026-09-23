<?php

namespace App\Http\Resources;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
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
        $canViewFinancials = ! in_array((int) $request->user()?->role, [
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ], true);
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

        $assignedAccounts = [];
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
            $assignedAccounts = $metaAccounts->map(fn ($a) => [
                'id' => (string) $a->id,
                'account_id' => $a->account_id,
                'account_name' => $a->account_name,
                'business_manager_id' => $a->business_manager_id,
                'amount_spent' => (float) ($a->amount_spent ?? 0),
                'currency' => $a->currency ?? 'USD',
                'account_status' => $a->account_status,
                'is_prepay_account' => $a->is_prepay_account,
                'timezone_name' => $a->timezone_name,
                'payment_card' => $a->payment_card,
            ])->values()->all();
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
            $assignedAccounts = $googleAccounts->map(fn ($a) => [
                'id' => (string) $a->id,
                'account_id' => $a->account_id,
                'account_name' => $a->account_name,
                'business_manager_id' => $a->customer_manager_id,
                'amount_spent' => (float) ($a->amount_spent ?? 0),
                'currency' => $a->currency ?? 'USD',
                'account_status' => $a->account_status,
                'timezone_name' => $a->time_zone,
            ])->values()->all();
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

        $walletBalance = (float) ($user?->wallet?->balance ?? 0.0);

        // Tính toán chi tiêu thực tế, đã thu và chưa thu theo từng tài khoản
        $spendingData = app(\App\Console\Commands\ServicesBillPostpay::class)->calculateSpendingAndUnbilled(
            $this->resource,
            is_array($config) ? $config : []
        );
        $totalSpend = (float) ($spendingData['total_spend'] ?? 0.0);
        $billedSpend = (float) ($spendingData['billed_spend'] ?? 0.0);
        $unbilledSpend = (float) ($spendingData['unbilled_spend'] ?? 0.0);

        $effectiveFeePercent = $spendingFeePercent;
        if ($effectiveFeePercent <= 0 && ($config['billing_source'] ?? '') === 'customer_card') {
            $effectiveFeePercent = $topUpFeePercent;
        }

        $pendingFee = round($unbilledSpend * ($effectiveFeePercent / 100), 2);
        $minWalletRequired = 100.0;
        $isLowBalance = $walletBalance < $minWalletRequired || ($pendingFee > 0 && $walletBalance < $pendingFee);

        $billingStatus = 'healthy';
        if ($isPostpay || ($config['billing_source'] ?? '') === 'customer_card') {
            if ($isLowBalance) {
                $billingStatus = 'low_balance';
            } elseif ($unbilledSpend >= 100.0) {
                $billingStatus = 'ready_to_charge';
            } else {
                $billingStatus = 'healthy';
            }
        } else {
            $billingStatus = 'prepay';
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
            'wallet_balance' => $walletBalance,
            'total_spend' => round($totalSpend, 2),
            'billed_spend' => round($billedSpend, 2),
            'unbilled_spend' => round($unbilledSpend, 2),
            'pending_fee' => $pendingFee,
            'billing_health' => [
                'status' => $billingStatus,
                'min_wallet_required' => $minWalletRequired,
                'is_low_balance' => $isLowBalance,
            ],
            'budget' => $this->budget,
            'open_fee' => $canViewFinancials ? $package?->open_fee : null,
            'top_up_fee' => $canViewFinancials ? $package?->top_up_fee : null,
            'spending_fee' => $canViewFinancials ? $spendingFeePercent : null,
            'total_cost' => $canViewFinancials ? $totalCost : null,
            'config_account' => $normalizedConfig,
            'assigned_accounts' => $assignedAccounts,
            'description' => $this->description,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
