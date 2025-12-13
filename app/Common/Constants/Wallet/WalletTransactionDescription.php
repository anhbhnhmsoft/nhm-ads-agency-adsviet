<?php

namespace App\Common\Constants\Wallet;

enum WalletTransactionDescription: string
{
    case DEPOSIT_CREATED = 'wallet.transaction_description.deposit_created';
    case WITHDRAW_CREATED_BANK = 'wallet.transaction_description.withdraw_created_bank';
    case WITHDRAW_CREATED_USDT = 'wallet.transaction_description.withdraw_created_usdt';
    case DEPOSIT_APPROVED = 'wallet.transaction_description.deposit_approved';
    case WITHDRAW_COMPLETED = 'wallet.transaction_description.withdraw_completed';
    case DEPOSIT_CANCELLED_USER = 'wallet.transaction_description.deposit_cancelled_user';
    case DEPOSIT_CANCELLED_ADMIN = 'wallet.transaction_description.deposit_cancelled_admin';
    case WITHDRAW_CANCELLED_USER = 'wallet.transaction_description.withdraw_cancelled_user';
    case WITHDRAW_CANCELLED_ADMIN = 'wallet.transaction_description.withdraw_cancelled_admin';
    case CAMPAIGN_BUDGET_UPDATE_CREATED = 'wallet.transaction_description.campaign_budget_update_created';
    case CAMPAIGN_BUDGET_UPDATE_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_budget_update_cancelled_admin';
    case CAMPAIGN_BUDGET_UPDATE_DETAIL = 'wallet.transaction_description.campaign_budget_update_detail';
    case CAMPAIGN_PAUSE_CREATED = 'wallet.transaction_description.campaign_pause_created';
    case CAMPAIGN_PAUSE_DETAIL = 'wallet.transaction_description.campaign_pause_detail';
    case CAMPAIGN_PAUSE_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_pause_cancelled_admin';
    case CAMPAIGN_END_CREATED = 'wallet.transaction_description.campaign_end_created';
    case CAMPAIGN_END_DETAIL = 'wallet.transaction_description.campaign_end_detail';
    case CAMPAIGN_END_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_end_cancelled_admin';

    public function getTranslationKey(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
}

