import type { ServicePackage } from '@/pages/service-purchase/types/type';

export type { ServicePackage };

export type CreateAccountPageProps = {
    packages: ServicePackage[];
    meta_timezones: Array<{ value: string; label: string }>;
    google_timezones: Array<{ value: string; label: string }>;
    postpay_permissions?: Record<string, boolean>;
};

export type CreateAccountFormData = {
    package_id: string;
    meta_email?: string;
    display_name?: string;
    bm_id?: string;
    info_fanpage?: string;
    info_website?: string;
    timezone_bm?: string;
    asset_access?: 'full_asset' | 'basic_asset';
    payment_type?: 'prepay' | 'postpay';
    top_up_amount?: string;
    budget?: string;
    accounts?: Array<{
        meta_email?: string;
        display_name?: string;
        bm_ids?: string[];
        fanpages?: string[];
        websites?: string[];
        timezone_bm?: string;
        asset_access?: 'full_asset' | 'basic_asset';
    }>;
    notes?: string;
};
