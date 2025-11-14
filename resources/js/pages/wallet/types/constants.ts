export const TRANSACTION_TYPE = {
    UNKNOWN: 0,
    DEPOSIT: 1,
    WITHDRAW: 2,
    REFUND: 3,
    FEE: 4,
    CASHBACK: 5,
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
};

export const TRANSACTION_STATUS_MAP: Record<number, string> = {
    [TRANSACTION_STATUS.UNKNOWN]: 'unknown',
    [TRANSACTION_STATUS.PENDING]: 'pending',
    [TRANSACTION_STATUS.APPROVED]: 'approved',
    [TRANSACTION_STATUS.REJECTED]: 'rejected',
    [TRANSACTION_STATUS.COMPLETED]: 'completed',
    [TRANSACTION_STATUS.CANCELLED]: 'cancelled',
};

