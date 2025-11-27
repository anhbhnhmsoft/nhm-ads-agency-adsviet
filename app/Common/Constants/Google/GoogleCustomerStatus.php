<?php

namespace App\Common\Constants\Google;

enum GoogleCustomerStatus: int
{
    case UNSPECIFIED = 0;
    case UNKNOWN = 1;
    case ENABLED = 2;
    case CANCELED = 3;
    case SUSPENDED = 4;
    case CLOSED = 5;

    public static function fromApiStatus(?string $status): self
    {
        return match (strtoupper((string) $status)) {
            'ENABLED' => self::ENABLED,
            'CANCELED' => self::CANCELED,
            'SUSPENDED' => self::SUSPENDED,
            'CLOSED' => self::CLOSED,
            'UNKNOWN' => self::UNKNOWN,
            default => self::UNSPECIFIED,
        };
    }
}

