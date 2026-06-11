<?php

namespace App\Common\Constants\Wallet;

enum WalletTransactionType: int
{
    case UNKNOWN = 0; // Không xác định
    case DEPOSIT = 1; // Nạp tiền
    case WITHDRAW = 2; // Rút tiền
    case REFUND = 3; // Hoàn tiền
    case FEE = 4; // Phí
    case CASHBACK = 5; // Cashback
    case SERVICE_PURCHASE = 6; // Thanh toán dịch vụ
    case CAMPAIGN_BUDGET_UPDATE_GOOGLE = 7; // Cập nhật ngân sách chiến dịch Google Ads
    case CAMPAIGN_BUDGET_UPDATE_META = 8;   // Cập nhật ngân sách chiến dịch Meta Ads
    case CAMPAIGN_PAUSE_GOOGLE = 9; // Tạm dừng chiến dịch Google Ads
    case CAMPAIGN_PAUSE_META = 10; // Tạm dừng chiến dịch Meta Ads
    case CAMPAIGN_END_GOOGLE = 11; // Kết thúc chiến dịch Google Ads
    case CAMPAIGN_END_META = 12; // Kết thúc chiến dịch Meta Ads
    case ACCOUNT_TOP_UP_GOOGLE = 13; // Nạp tiền tài khoản Google Ads
    case ACCOUNT_TOP_UP_META = 14; // Nạp tiền tài khoản Meta Ads
    case SPENDING_FEE = 15; // Phí spending trả sau

    public static function getOptions(): array
    {
        return [
            self::UNKNOWN->value => __('wallet.transaction_type.unknown'),
            self::DEPOSIT->value => __('wallet.transaction_type.deposit'),
            self::WITHDRAW->value => __('wallet.transaction_type.withdraw'),
            self::REFUND->value => __('wallet.transaction_type.refund'),
            self::FEE->value => __('wallet.transaction_type.fee'),
            self::CASHBACK->value => __('wallet.transaction_type.cashback'),
            self::SERVICE_PURCHASE->value => __('wallet.transaction_type.service_purchase'),
            self::CAMPAIGN_BUDGET_UPDATE_GOOGLE->value => __('wallet.transaction_type.campaign_budget_update_google'),
            self::CAMPAIGN_BUDGET_UPDATE_META->value => __('wallet.transaction_type.campaign_budget_update_meta'),
            self::CAMPAIGN_PAUSE_GOOGLE->value => __('wallet.transaction_type.campaign_pause_google'),
            self::CAMPAIGN_PAUSE_META->value => __('wallet.transaction_type.campaign_pause_meta'),
            self::CAMPAIGN_END_GOOGLE->value => __('wallet.transaction_type.campaign_end_google'),
            self::CAMPAIGN_END_META->value => __('wallet.transaction_type.campaign_end_meta'),
            self::ACCOUNT_TOP_UP_GOOGLE->value => __('wallet.transaction_type.account_top_up_google'),
            self::ACCOUNT_TOP_UP_META->value => __('wallet.transaction_type.account_top_up_meta'),
            self::SPENDING_FEE->value => __('wallet.transaction_type.spending_fee'),
        ];
    }
}
