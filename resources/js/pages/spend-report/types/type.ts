export type AccountSpend = {
    account_id: string;
    account_name?: string;
    amount_spent: number;
};

export type ReportData = {
    total_spend: number;
    today_spend: number;
    account_spend: AccountSpend[];
};

export type ChartDataPoint = {
    value: number;
    date: string;
};

export type InsightData = {
    total_spend_period: number;
    chart: ChartDataPoint[];
};

export type SpendReportPageProps = {
    reportData: ReportData | null;
    insightData: InsightData | null;
    selectedPlatform: string;
    selectedDatePreset: string;
    error: string | null;
};

export type DatePresetOption = {
    value: string;
    label: string;
};

