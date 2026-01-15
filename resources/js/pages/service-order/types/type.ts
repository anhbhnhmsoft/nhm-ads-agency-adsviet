import type { LaravelPaginator } from '@/lib/types/type';

/**
 * Account configuration data structure (new format)
 */
export type AccountConfig = {
    meta_email?: string;
    display_name?: string;
    bm_ids?: string[];
    fanpages?: string[];
    websites?: string[];
    timezone_bm?: string;
    asset_access?: 'full_asset' | 'basic_asset';
};

/**
 * Service order config_account structure
 * Supports both legacy (flat) and new (nested accounts array) formats
 */
export type ServiceOrderConfigAccount = {
    // New structure with multiple accounts
    accounts?: AccountConfig[];

    // Legacy flat structure (backward compatible)
    meta_email?: string;
    display_name?: string;
    bm_id?: string;
    info_fanpage?: string;
    info_website?: string;
    timezone_bm?: string;
    asset_access?: 'full_asset' | 'basic_asset';

    // Common fields
    payment_type?: 'prepay' | 'postpay';
    top_up_amount?: number | string;
    open_fee_paid?: boolean;
};

export type ServiceOrder = {
    id: string;
    status: number;
    status_label?: string | null;
    package: {
        id?: string | null;
        name?: string | null;
        platform?: number | null;
        platform_label?: string | null;
    };
    user?: {
        referrer?: {
            name?: string | null;
        } | null;
    } | null;
    budget: string;
    open_fee?: string;
    top_up_fee?: number;
    total_cost?: number; // Tổng chi phí được tính ở backend
    config_account?: ServiceOrderConfigAccount | null;
    description?: string | null;
    created_at?: string | null;
};

export type ServiceOrderPagination = LaravelPaginator<ServiceOrder>;
