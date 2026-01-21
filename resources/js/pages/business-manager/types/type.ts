import type { LaravelPaginator } from '@/lib/types/type';
import { _PlatformType } from '@/lib/types/constants';

export type BusinessManagerListQuery = {
    filter: {
        keyword?: string;
        platform?: _PlatformType | number | undefined;
        start_date?: string;
        end_date?: string;
    };
    sort_by?: string;
    direction?: 'asc' | 'desc';
};

// Config account structure
export type BusinessManagerConfigAccount = {
    display_name?: string | null;
};

// Item in the BM/MCC list
export type BusinessManagerItem = {
    id: string;
    account_id?: string;
    account_name?: string | null;
    bm_ids?: string[];
    bm_name?: string | null;
    name: string;
    platform: _PlatformType | number;
    service_user_id?: string;
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
    config_account?: BusinessManagerConfigAccount | null;
    is_business_manager?: boolean;
    parent_bm_id?: string | null;
    child_bm_id?: string | null;
};

export type ChildBusinessManager = {
    bm_id: string;
    name: string;
    parent_bm_id: string;
    verification_status?: string | null;
    currency?: string | null;
};

// Detail account item inside dialog
export type BusinessManagerAccount = {
    account_id: string;
    account_name: string;
    spend_cap?: string | null;
    amount_spent?: string | null;
    total_campaigns?: number;
    currency?: string | null;
    service_user_id?: string;
    id?: string;
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

