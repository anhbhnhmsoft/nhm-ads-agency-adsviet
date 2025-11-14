<?php

namespace App\Common\Constants\Wallet;

enum WalletTransactionStatus: int
{
    case UNKNOWN = 0; // Không xác định
    case PENDING = 1; // Chờ xử lý
    case APPROVED = 2; // Đã duyệt
    case REJECTED = 3; // Từ chối
    case COMPLETED = 4; // Hoàn thành
    case CANCELLED = 5; // Đã hủy

    public static function getOptions(): array
    {
        return [
            self::UNKNOWN->value => __('wallet.transaction_status.unknown'),
            self::PENDING->value => __('wallet.transaction_status.pending'),
            self::APPROVED->value => __('wallet.transaction_status.approved'),
            self::REJECTED->value => __('wallet.transaction_status.rejected'),
            self::COMPLETED->value => __('wallet.transaction_status.completed'),
            self::CANCELLED->value => __('wallet.transaction_status.cancelled'),
        ];
    }
}


