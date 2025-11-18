<?php

namespace App\Common\Constants\ServiceUser;

enum ServiceUserTransactionStatus: int
{
    case UNKNOWN = 0; // Không xác định
    case PENDING = 1; // Chờ xử lý
    case COMPLETED = 2; // Hoàn thành
    case FAILED = 3; // Thất bại
    case CANCELLED = 4; // Đã hủy

    public static function getOptions(): array
    {
        return [
            self::UNKNOWN->value => __('service_user.transaction_status.unknown'),
            self::PENDING->value => __('service_user.transaction_status.pending'),
            self::COMPLETED->value => __('service_user.transaction_status.completed'),
            self::FAILED->value => __('service_user.transaction_status.failed'),
            self::CANCELLED->value => __('service_user.transaction_status.cancelled'),
        ];
    }
}

