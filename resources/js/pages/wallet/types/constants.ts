export const TRANSACTION_TYPE = {
    UNKNOWN: 0,
    DEPOSIT: 1,
    WITHDRAW: 2,
    REFUND: 3,
    FEE: 4,
    CASHBACK: 5,
    SERVICE_PURCHASE: 6,
    CAMPAIGN_BUDGET_UPDATE_GOOGLE: 7,
    CAMPAIGN_BUDGET_UPDATE_META: 8,
    CAMPAIGN_PAUSE_GOOGLE: 9,
    CAMPAIGN_PAUSE_META: 10,
    CAMPAIGN_END_GOOGLE: 11,
    CAMPAIGN_END_META: 12,
} as const;

export const TRANSACTION_STATUS = {
    UNKNOWN: 0,
    PENDING: 1,
    APPROVED: 2,
    REJECTED: 3,
    COMPLETED: 4,
    CANCELLED: 5,
} as const;

export const TRANSACTION_TYPE_MAP: Record<number, string> = {
    [TRANSACTION_TYPE.UNKNOWN]: 'unknown',
    [TRANSACTION_TYPE.DEPOSIT]: 'deposit',
    [TRANSACTION_TYPE.WITHDRAW]: 'withdraw',
    [TRANSACTION_TYPE.REFUND]: 'refund',
    [TRANSACTION_TYPE.FEE]: 'fee',
    [TRANSACTION_TYPE.CASHBACK]: 'cashback',
    [TRANSACTION_TYPE.SERVICE_PURCHASE]: 'service_purchase',
    [TRANSACTION_TYPE.CAMPAIGN_BUDGET_UPDATE_GOOGLE]: 'campaign_budget_update_google',
    [TRANSACTION_TYPE.CAMPAIGN_BUDGET_UPDATE_META]: 'campaign_budget_update_meta',
    [TRANSACTION_TYPE.CAMPAIGN_PAUSE_GOOGLE]: 'campaign_pause_google',
    [TRANSACTION_TYPE.CAMPAIGN_PAUSE_META]: 'campaign_pause_meta',
    [TRANSACTION_TYPE.CAMPAIGN_END_GOOGLE]: 'campaign_end_google',
    [TRANSACTION_TYPE.CAMPAIGN_END_META]: 'campaign_end_meta',
};

export const TRANSACTION_STATUS_MAP: Record<number, string> = {
    [TRANSACTION_STATUS.UNKNOWN]: 'unknown',
    [TRANSACTION_STATUS.PENDING]: 'pending',
    [TRANSACTION_STATUS.APPROVED]: 'approved',
    [TRANSACTION_STATUS.REJECTED]: 'rejected',
    [TRANSACTION_STATUS.COMPLETED]: 'completed',
    [TRANSACTION_STATUS.CANCELLED]: 'cancelled',
};

