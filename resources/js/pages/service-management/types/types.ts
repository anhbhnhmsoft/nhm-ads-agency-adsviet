import type { ServiceOrder } from '@/pages/service-order/types/type';

export type MetaAccount = {
    id: string;
    account_id: string;
    account_name?: string | null;
    account_status?: number | null;
    spend_cap?: string | null;
    balance?: string | null;
    currency?: string | null;
    last_synced_at?: string | null;
};

export type MetaCampaign = {
    id: string;
    campaign_id: string;
    name?: string | null;
    status?: string | null;
    effective_status?: string | null;
    objective?: string | null;
    daily_budget?: string | null;
    budget_remaining?: string | null;
    start_time?: string | null;
    stop_time?: string | null;
};

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
    date_start?: string;
    date_stop?: string;
    spend?: string | number | null;
    impressions?: number | null;
    clicks?: number | null;
    cpc?: number | null;
    cpm?: number | null;
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
