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
        ];
    }
}


