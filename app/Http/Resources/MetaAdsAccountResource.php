<?php

namespace App\Http\Resources;

use App\Common\Constants\ServicePackage\Meta\MetaAdsAccountStatus;
use App\Common\Constants\ServicePackage\Meta\MetaAdsDisableReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaAdsAccountResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $status = MetaAdsAccountStatus::fromValue($this->account_status);

        // Tính trạng thái hết tiền (không lưu DB, chỉ tính động)
        $spendCap = $this->normalizeMetaAccountMoney($this->spend_cap, $this->currency);
        $amountSpent = $this->normalizeMetaAccountMoney($this->amount_spent, $this->currency);
        $balance = $this->normalizeMetaAccountMoney($this->balance, $this->currency);
        $balanceExhausted = false;

        // Case 1: nếu có balance -> xem là hết tiền khi balance <= 0
        // (áp dụng cho cả tài khoản trả trước và trả sau, để UI cảnh báo giống Google Ads)
        if ($balance !== null) {
            $balanceExhausted = $balance <= 0;
        }

        // Case 2: có spend_cap -> hết tiền khi amount_spent >= spend_cap
        if (!$balanceExhausted && $spendCap !== null && $spendCap > 0 && $amountSpent !== null) {
            $balanceExhausted = $amountSpent >= $spendCap;
        }

        // Map disable_reason (chỉ hiển thị khi > 0)
        $disableReasonEnum = null;
        $disableReasonCode = null;
        if ($this->disable_reason !== null && $this->disable_reason !== '') {
            $code = (int) $this->disable_reason;
            if ($code > 0) {
                $disableReasonEnum = MetaAdsDisableReason::fromValue($code);
                $disableReasonCode = $disableReasonEnum?->value;
            }
        }

        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'account_id' => $this->account_id,
            'account_name' => $this->account_name,
            'account_status' => $this->account_status,
            'status_label' => $status?->label(),
            'status_severity' => $status?->severity(),
            'status_message' => $status?->message(),
            'disable_reason' => $disableReasonEnum?->label(),
            'disable_reason_code' => $disableReasonCode,
            'disable_reason_severity' => $disableReasonEnum?->severity(),
            'spend_cap' => $spendCap,
            'amount_spent' => $amountSpent,
            'balance' => $balance,
            'balance_exhausted' => $balanceExhausted,
            'currency' => $this->currency,
            'is_prepay_account' => $this->is_prepay_account,
            'timezone_id' => $this->timezone_id,
            'timezone_name' => $this->timezone_name,
            'payment_card' => $this->payment_card,
            'last_synced_at' => $this->last_synced_at,
        ];
    }

    private function normalizeMetaAccountMoney(mixed $value, ?string $currency): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;
        $currency = strtoupper((string) ($currency ?: 'USD'));
        $zeroDecimalCurrencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW',
            'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ];

        return in_array($currency, $zeroDecimalCurrencies, true)
            ? $amount
            : $amount / 100;
    }
}
