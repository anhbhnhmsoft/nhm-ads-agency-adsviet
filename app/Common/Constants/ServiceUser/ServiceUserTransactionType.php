<?php

namespace App\Common\Constants\ServiceUser;

enum ServiceUserTransactionType: int
{
    case UNKNOWN = 0; // Không xác định
    case PURCHASE = 1; // Mua dịch vụ
    case TOP_UP = 2; // Nạp tiền vào dịch vụ
    case REFUND = 3; // Hoàn tiền
    case FEE = 4; // Phí dịch vụ

    public static function getOptions(): array
    {
        return [
            self::UNKNOWN->value => __('service_user.transaction_type.unknown'),
            self::PURCHASE->value => __('service_user.transaction_type.purchase'),
            self::TOP_UP->value => __('service_user.transaction_type.top_up'),
            self::REFUND->value => __('service_user.transaction_type.refund'),
            self::FEE->value => __('service_user.transaction_type.fee'),
        ];
    }
}

