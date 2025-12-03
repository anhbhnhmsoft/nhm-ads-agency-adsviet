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

    public static function fromApiStatus(mixed $status): self
    {
        if (is_int($status) || (is_string($status) && ctype_digit($status))) {
            return match ((int) $status) {
                2 => self::ENABLED,
                3 => self::CANCELED,
                4 => self::SUSPENDED,
                5 => self::CLOSED,
                1 => self::UNKNOWN,
                default => self::UNSPECIFIED,
            };
        }

        return match (strtoupper((string) $status)) {
            'ENABLED' => self::ENABLED,
            'CANCELED' => self::CANCELED,
            'SUSPENDED' => self::SUSPENDED,
            'CLOSED' => self::CLOSED,
            'UNKNOWN' => self::UNKNOWN,
            default => self::UNSPECIFIED,
        };
    }

    public static function fromValue(?int $value): ?self
    {
        return $value !== null ? self::tryFrom($value) : null;
    }

    public function label(): string
    {
        return match ($this) {
            self::ENABLED => __('google_ads.account_status.enabled'),
            self::CANCELED => __('google_ads.account_status.canceled'),
            self::SUSPENDED => __('google_ads.account_status.suspended'),
            self::CLOSED => __('google_ads.account_status.closed'),
            default => __('google_ads.account_status.unknown'),
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::ENABLED => 'success',
            self::CANCELED, self::SUSPENDED, self::CLOSED => 'error',
            default => 'warning',
        };
    }

    public function message(): ?string
    {
        return match ($this) {
            self::CANCELED => __('google_ads.account_status_messages.canceled'),
            self::SUSPENDED => __('google_ads.account_status_messages.suspended'),
            self::CLOSED => __('google_ads.account_status_messages.closed'),
            default => null,
        };
    }
}

