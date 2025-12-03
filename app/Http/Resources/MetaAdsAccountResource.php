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
        $spendCap = $this->spend_cap !== null ? (float) $this->spend_cap : null;
        $amountSpent = $this->amount_spent !== null ? (float) $this->amount_spent : null;
        $balance = $this->balance !== null ? (float) $this->balance : null;
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
            'spend_cap' => $this->spend_cap,
            'amount_spent' => $this->amount_spent,
            'balance' => $this->balance,
            'balance_exhausted' => $balanceExhausted,
            'currency' => $this->currency,
            'is_prepay_account' => $this->is_prepay_account,
            'timezone_id' => $this->timezone_id,
            'timezone_name' => $this->timezone_name,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
