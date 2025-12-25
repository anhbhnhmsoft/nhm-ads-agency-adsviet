import type { LaravelPaginator } from '@/lib/types/type';
import { _PlatformType } from '@/lib/types/constants';

export type BusinessManagerListQuery = {
    filter: {
        keyword?: string;
        platform?: _PlatformType | number | undefined;
    };
    sort_by?: string;
    direction?: 'asc' | 'desc';
};

// Item in the BM/MCC list
export type BusinessManagerItem = {
    id: string;
    name: string;
    platform: _PlatformType | number;
    owner_name?: string | null;
    owner_id?: string | null;
    total_accounts: number;
    active_accounts: number;
    disabled_accounts: number;
    total_spend?: string | null;
    total_balance?: string | null;
    currency?: string | null;
    accounts?: Array<{
        currency?: string | null;
    }>;
    config_account?: {
        display_name?: string | null;
    } | null;
};

// Detail account item inside dialog
export type BusinessManagerAccount = {
    account_id: string;
    account_name: string;
    spend_cap?: string | null;
    amount_spent?: string | null;
    total_campaigns?: number;
    currency?: string | null;
};

export type BusinessManagerPagination = LaravelPaginator<BusinessManagerItem>;

export type BusinessManagerStats = {
    total_accounts: number;
    active_accounts: number;
    disabled_accounts: number;
    by_platform: Record<number, {
        total_accounts: number;
        active_accounts: number;
        disabled_accounts: number;
    }>;
};

