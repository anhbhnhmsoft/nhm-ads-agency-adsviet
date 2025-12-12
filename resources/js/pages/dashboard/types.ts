import type { LaravelPaginator } from '@/lib/types/type';

export type DashboardData = {
    wallet: {
        balance: string;
    };
    overview: {
        total_accounts: number;
        active_accounts: number;
        paused_accounts: number;
        total_spend: string;
        today_spend: string;
        total_services: number;
        available_services: number;
        critical_alerts: number;
        accounts_with_errors: number;
    };
    metrics: {
        total_spend: {
            value: string;
            percent_change: number;
        };
        today_spend: {
            value: string;
            percent_change: number;
        };
        total_impressions: {
            value: string;
            percent_change: number;
        };
        total_clicks: {
            value: string;
            percent_change: number;
        };
        total_conversions: {
            value: number;
            percent_change: number;
        };
        active_accounts: {
            active: number;
            total: number;
        };
    };
    performance: {
        conversion_rate: string;
        avg_cpc: string;
        avg_roas: string;
    };
    budget: {
        total: string;
        used: string;
        remaining: string;
        usage_percent: string;
        is_postpay?: boolean;
    };
    alerts: {
        critical_errors: number;
        accounts_with_errors: number;
    };
};

export type AdminPendingTransaction = {
    id: string;
    amount: number;
    type: number;
    status: number;
    description?: string | null;
    network?: string | null;
    created_at?: string | null;
    customer_id?: number | null;
    customer_name?: string | null;
    customer_email?: string | null;
    withdraw_info?: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
        crypto_address?: string;
        network?: 'TRC20' | 'BEP20';
        withdraw_type?: 'bank' | 'usdt';
    } | null;
};

export type SpendingRankingItem = {
    rank: number;
    account_id: string;
    account_name: string;
    account_id_display: string;
    account_status: number;
    status_label: string;
    total_spend: number;
};

export type SpendingRanking = {
    data: SpendingRankingItem[];
    platform: string;
    start_date: string;
    end_date: string;
} | null;

export type AdminDashboardData = {
    total_customers: number;
    active_customers: number;
    pending_transactions: number;
    platform_accounts?: {
        google: {
            active_accounts: number;
            total_balance: string;
        };
        meta: {
            active_accounts: number;
            total_balance: string;
        };
    };
    pending_tickets_by_type?: {
        transfer_budget: number;
        account_liquidation: number;
        account_appeal: number;
        share_bm: number;
        wallet_withdraw_app: number;
    };
    spending_ranking?: SpendingRanking;
};

export type AdminPendingTransactions = LaravelPaginator<AdminPendingTransaction>;

