export enum WalletTransactionDescription {
    DEPOSIT_CREATED = 'wallet.transaction_description.deposit_created',
    WITHDRAW_CREATED_BANK = 'wallet.transaction_description.withdraw_created_bank',
    WITHDRAW_CREATED_USDT = 'wallet.transaction_description.withdraw_created_usdt',
    DEPOSIT_APPROVED = 'wallet.transaction_description.deposit_approved',
    WITHDRAW_COMPLETED = 'wallet.transaction_description.withdraw_completed',
    DEPOSIT_CANCELLED_USER = 'wallet.transaction_description.deposit_cancelled_user',
    WITHDRAW_CANCELLED_USER = 'wallet.transaction_description.withdraw_cancelled_user',
    WITHDRAW_CANCELLED_ADMIN = 'wallet.transaction_description.withdraw_cancelled_admin',
    CAMPAIGN_BUDGET_UPDATE_CREATED = 'wallet.transaction_description.campaign_budget_update_created',
    CAMPAIGN_BUDGET_UPDATE_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_budget_update_cancelled_admin',
    CAMPAIGN_BUDGET_UPDATE_DETAIL = 'wallet.transaction_description.campaign_budget_update_detail',
    CAMPAIGN_PAUSE_CREATED = 'wallet.transaction_description.campaign_pause_created',
    CAMPAIGN_PAUSE_DETAIL = 'wallet.transaction_description.campaign_pause_detail',
    CAMPAIGN_PAUSE_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_pause_cancelled_admin',
    CAMPAIGN_END_CREATED = 'wallet.transaction_description.campaign_end_created',
    CAMPAIGN_END_DETAIL = 'wallet.transaction_description.campaign_end_detail',
    CAMPAIGN_END_CANCELLED_ADMIN = 'wallet.transaction_description.campaign_end_cancelled_admin',
}

export function getTransactionDescription(
    description: string | null | undefined,
    t: (key: string, opts?: Record<string, any>) => string
): string {
    if (!description) {
        return t('wallet.transaction_description.unknown', { defaultValue: 'Không xác định' });
    }

    if (description.startsWith('wallet.transaction_description.')) {
        return t(description, { defaultValue: description });
    }

    return description;
}

