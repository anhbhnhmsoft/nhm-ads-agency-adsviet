<?php

namespace App\Common\Constants\ServicePackage\Meta;

enum MetaAdsAccountStatus: int
{
    case ACTIVE = 1;
    case DISABLED = 2;
    case UNSETTLED = 3;
    case PENDING_RISK_REVIEW = 7;
    case PENDING_SETTLEMENT = 8;
    case IN_GRACE_PERIOD = 9;
    case PENDING_CLOSURE = 100;
    case CLOSED = 101;
    case ANY_ACTIVE = 201;
    case ANY_CLOSED = 202;

    public static function fromValue(?int $value): ?self
    {
        return $value !== null ? self::tryFrom($value) : null;
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE, self::ANY_ACTIVE => __('meta.account_status.active'),
            self::DISABLED => __('meta.account_status.disabled'),
            self::UNSETTLED => __('meta.account_status.unsettled'),
            self::PENDING_RISK_REVIEW => __('meta.account_status.pending_risk_review'),
            self::PENDING_SETTLEMENT => __('meta.account_status.pending_settlement'),
            self::IN_GRACE_PERIOD => __('meta.account_status.in_grace_period'),
            self::PENDING_CLOSURE => __('meta.account_status.pending_closure'),
            self::CLOSED, self::ANY_CLOSED => __('meta.account_status.closed'),
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::ACTIVE, self::ANY_ACTIVE => 'success',
            self::PENDING_RISK_REVIEW, self::PENDING_SETTLEMENT, self::IN_GRACE_PERIOD => 'warning',
            default => 'error',
        };
    }

    public function message(): ?string
    {
        return match ($this) {
            self::DISABLED => __('meta.account_status_messages.disabled'),
            self::UNSETTLED => __('meta.account_status_messages.unsettled'),
            self::PENDING_RISK_REVIEW => __('meta.account_status_messages.pending_risk_review'),
            self::PENDING_SETTLEMENT => __('meta.account_status_messages.pending_settlement'),
            self::IN_GRACE_PERIOD => __('meta.account_status_messages.in_grace_period'),
            self::PENDING_CLOSURE, self::ANY_CLOSED => __('meta.account_status_messages.pending_closure'),
            self::CLOSED => __('meta.account_status_messages.closed'),
            default => null,
        };
    }
}
