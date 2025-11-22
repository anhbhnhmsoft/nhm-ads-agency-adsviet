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
    } | null;
};

export type AdminDashboardData = {
    total_customers: number;
    active_customers: number;
    pending_transactions: number;
};

export type AdminPendingTransactions = LaravelPaginator<AdminPendingTransaction>;

