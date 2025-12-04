import type { ServiceOrder } from '@/pages/service-order/types/type';

export type StatusSeverity = 'success' | 'warning' | 'error' | null;

export type MetaAccount = {
    id: string;
    account_id: string;
    account_name?: string | null;
    account_status?: number | null;
    status_label?: string | null;
    status_severity?: StatusSeverity;
    status_message?: string | null;
    disable_reason?: string | null;
    disable_reason_code?: number | null;
    disable_reason_severity?: StatusSeverity;
    spend_cap?: string | null;
    balance?: string | null;
    currency?: string | null;
    last_synced_at?: string | null;
};

export type GoogleAccount = {
    id: string;
    account_id: string;
    account_name?: string | null;
    account_status?: number | null;
    status_label?: string | null;
    status_severity?: StatusSeverity;
    status_message?: string | null;
    disable_reason?: string | null;
    disable_reason_code?: number | null;
    disable_reason_severity?: StatusSeverity;
    currency?: string | null;
    customer_manager_id?: string | null;
    time_zone?: string | null;
    primary_email?: string | null;
    balance?: number | string | null;
    balance_exhausted?: boolean | null;
    last_synced_at?: string | null;
};

export type AdAccount = MetaAccount | GoogleAccount;

export type MetaCampaign = {
    id: string;
    campaign_id: string;
    name?: string | null;
    status?: string | null;
    effective_status?: string | null;
    status_label?: string | null;
    status_severity?: StatusSeverity;
    objective?: string | null;
    daily_budget?: string | null;
    budget_remaining?: string | null;
    start_time?: string | null;
    stop_time?: string | null;
    total_spend?: string | null;
    today_spend?: string | null;
};

export type GoogleAdsCampaign = {
    id: string;
    campaign_id: string;
    name?: string | null;
    status?: string | null;
    effective_status?: string | null;
    status_label?: string | null;
    status_severity?: StatusSeverity;
    objective?: string | null;
    daily_budget?: string | null;
    budget_remaining?: string | null;
    start_time?: string | null;
    stop_time?: string | null;
    total_spend?: string | null;
    today_spend?: string | null;
};

export type Campaign = MetaCampaign | GoogleAdsCampaign;

type MetricValue = {
    today: number | string | null;
    total: number | string | null;
    percent_change: number | string | null;
};

export type CampaignDetail = {
    id: string;
    service_user_id: string;
    meta_account_id: string;
    campaign_id: string;
    name: string;
    status: string;
    effective_status: string;
    objective: string;
    daily_budget: string;
    budget_remaining: string;
    created_time: string;
    start_time: string;
    stop_time: string | null;
    last_synced_at: string;
    today_spend: number | string | null;
    total_spend: number | string | null;
    cpc_avg: number | string | null;
    cpm_avg: number | string | null;
    roas_avg: number | string | null;
    insight: {
        spend: MetricValue;
        impressions: MetricValue;
        clicks: MetricValue;
        cpc: MetricValue;
        cpm: MetricValue;
        actions: MetricValue;
        [key: string]: MetricValue;
    };
};

export type CampaignDailyInsight = {
    date_start?: string; // Meta Ads
    date_stop?: string; // Meta Ads
    date?: string; // Google Ads (format: Y-m-d)
    spend?: string | number | null;
    impressions?: number | null;
    clicks?: number | null;
    cpc?: number | null;
    cpm?: number | null;
    conversions?: number | null;
    ctr?: number | null;
    roas?: number | null;
};

export type ServiceManagementHook = {
    services: ServiceOrder[];
    selectedService: ServiceOrder | null;
    accounts: MetaAccount[];
    accountsLoading: boolean;
    accountsError: string | null;
    campaignsByAccount: Record<string, MetaCampaign[]>;
    campaignLoadingId: string | null;
    campaignError: string | null;
    selectedAccountId: string | null;
    handleViewService: (service: ServiceOrder) => Promise<void>;
    handleLoadCampaigns: (account: MetaAccount) => Promise<void>;
    closeDialog: () => void;
};
