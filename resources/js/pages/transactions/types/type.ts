import type { WalletTransaction } from '@/pages/wallet/types/type';

export type Transaction = WalletTransaction;

export interface TransactionsIndexProps {
    transactions: Transaction[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        type?: string;
        status?: string;
        user_id?: string;
    };
    canApprove: boolean;
}

