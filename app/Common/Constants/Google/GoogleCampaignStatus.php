<?php

namespace App\Common\Constants\Google;

enum GoogleCampaignStatus: string
{
    case ENABLED = 'ENABLED';
    case PAUSED = 'PAUSED';
    case REMOVED = 'REMOVED';
    case UNKNOWN = 'UNKNOWN';

    public static function fromApiStatus(mixed $status): self
    {
        if (is_int($status)) {
            // Google Ads CampaignStatus enum values
            // UNSPECIFIED = 0, UNKNOWN = 1, ENABLED = 2, PAUSED = 3, REMOVED = 4
            return match ($status) {
                2 => self::ENABLED,
                3 => self::PAUSED,
                4 => self::REMOVED,
                default => self::UNKNOWN,
            };
        }

        return match (strtoupper((string) $status)) {
            'ENABLED' => self::ENABLED,
            'PAUSED' => self::PAUSED,
            'REMOVED' => self::REMOVED,
            default => self::UNKNOWN,
        };
    }

    public static function fromValue(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }
        return self::tryFrom(strtoupper($value));
    }

    public function label(): string
    {
        return match ($this) {
            self::ENABLED => __('google_ads.campaign_status.enabled'),
            self::PAUSED => __('google_ads.campaign_status.paused'),
            self::REMOVED => __('google_ads.campaign_status.removed'),
            default => __('google_ads.campaign_status.unknown'),
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::ENABLED => 'success',
            self::PAUSED => 'warning',
            self::REMOVED => 'error',
            default => 'warning',
        };
    }
}

