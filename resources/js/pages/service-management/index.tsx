import { useMemo, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { ServiceOrder, ServiceOrderPagination } from '@/pages/service-order/types/type';
import type {
    AdAccount,
    MetaAccount,
    GoogleAccount,
    MetaCampaign,
    GoogleAdsCampaign,
    Campaign,
    CampaignDetail,
    CampaignDailyInsight,
    StatusSeverity,
} from '@/pages/service-management/types/types';
import { _PlatformType, _UserRole } from '@/lib/types/constants';
import type { IUser } from '@/lib/types/type';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTablePagination } from '@/components/table/pagination';
import axios from 'axios';
import { useEffect, useRef } from 'react';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from 'react-i18next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Loader2, Radio, ArrowLeft, TrendingUp, TrendingDown } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import type { ValueType } from 'recharts/types/component/DefaultTooltipContent';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { toast } from 'sonner';
import { wallet_me_json } from '@/routes';

type Props = {
    paginator: ServiceOrderPagination;
};

const ServiceManagementIndex = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const authUser = useMemo(() => {
        const authProp = props.auth as { user?: IUser | null } | IUser | null | undefined;
        if (authProp && typeof authProp === 'object' && 'user' in authProp) {
            return authProp.user ?? null;
        }
        return (authProp as IUser | null) ?? null;
    }, [props.auth]);
    const services = paginator?.data ?? [];

    const [selectedService, setSelectedService] = useState<ServiceOrder | null>(null);
    const [accounts, setAccounts] = useState<AdAccount[]>([]);
    const [accountsLoading, setAccountsLoading] = useState(false);
    const [accountsError, setAccountsError] = useState<string | null>(null);

    const [campaignsByAccount, setCampaignsByAccount] = useState<Record<string, Campaign[]>>({});
    const [campaignLoadingId, setCampaignLoadingId] = useState<string | null>(null);
    const [campaignError, setCampaignError] = useState<string | null>(null);
    const [selectedAccountId, setSelectedAccountId] = useState<string | null>(null);
    const [selectedCampaign, setSelectedCampaign] = useState<Campaign | null>(null);
    const [campaignDetail, setCampaignDetail] = useState<CampaignDetail | null>(null);
    const [campaignDetailLoading, setCampaignDetailLoading] = useState(false);
    const [campaignDetailError, setCampaignDetailError] = useState<string | null>(null);
    const [insightPreset, setInsightPreset] = useState<'last_7d' | 'last_30d'>('last_7d');
    const [campaignInsights, setCampaignInsights] = useState<CampaignDailyInsight[]>([]);
    const [campaignInsightsLoading, setCampaignInsightsLoading] = useState(false);
    const [campaignInsightsError, setCampaignInsightsError] = useState<string | null>(null);

    // State cho dialog cập nhật ngân sách
    const [budgetDialogOpen, setBudgetDialogOpen] = useState(false);
    const [budgetAmount, setBudgetAmount] = useState('');
    const [budgetWalletPassword, setBudgetWalletPassword] = useState('');
    const [budgetSubmitting, setBudgetSubmitting] = useState(false);
    const [walletBalance, setWalletBalance] = useState<number | null>(null);
    const [walletBalanceLoading, setWalletBalanceLoading] = useState(false);

    // State cho dialog tạm dừng/kết thúc chiến dịch
    const [pauseDialogOpen, setPauseDialogOpen] = useState(false);
    const [pauseSubmitting, setPauseSubmitting] = useState(false);
    const [resumeDialogOpen, setResumeDialogOpen] = useState(false);
    const [resumeSubmitting, setResumeSubmitting] = useState(false);
    const [endDialogOpen, setEndDialogOpen] = useState(false);
    const [endSubmitting, setEndSubmitting] = useState(false);
    const currentUserRole = authUser?.role;
    const isAgencyOrCustomer =
        currentUserRole === _UserRole.AGENCY || currentUserRole === _UserRole.CUSTOMER;

    const parseNumber = (value: number | string | null | undefined): number | null => {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : null;
        }
        if (typeof value === 'string') {
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : null;
        }
        return null;
    };

    const formatNumber = (
        value: number | string | null | undefined,
        options: Intl.NumberFormatOptions = { maximumFractionDigits: 2 }
    ): string => {
        const parsed = parseNumber(value);
        if (parsed === null) {
            return '--';
        }
        return parsed.toLocaleString(undefined, options);
    };

    const formatCurrency = (value: number | string | null | undefined): string => {
        const formatted = formatNumber(value, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        return formatted === '--' ? '--' : `${formatted} USDT`;
    };

    const formatPercentChange = (value: number | string | null | undefined): string => {
        const parsed = parseNumber(value);
        if (parsed === null) {
            return '--';
        }
        const formatted = parsed.toFixed(2);
        return `${parsed >= 0 ? '+' : ''}${formatted}%`;
    };

    // Normalize cpc và cpm từ integer thành float để tránh lỗi type mismatch
    const normalizeInsightItem = (item: any): CampaignDailyInsight => {
        return {
            ...item,
            // Đảm bảo cpc và cpm luôn là number (float), không phải integer
            cpc: item.cpc != null ? Number(item.cpc) : null,
            cpm: item.cpm != null ? Number(item.cpm) : null,
        };
    };

    const insightPresetOptions = useMemo(
        () => [
            { value: 'last_7d', label: t('service_management.spend_chart_preset_7d') },
            { value: 'last_30d', label: t('service_management.spend_chart_preset_30d') },
        ],
        [t]
    );

    // Tự động mở service đầu tiên khi có filter keyword (đi từ BM/MCC) vì có thể nhập trùng bm vfa mcc
    const hasAutoOpenedRef = useRef(false);
    const urlKeyword =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('filter[keyword]')
            : null;

    useEffect(() => {
        if (hasAutoOpenedRef.current) return;
        if (urlKeyword && services.length > 0 && !selectedService) {
            hasAutoOpenedRef.current = true;
            handleViewService(services[0]);
        }
    }, [services, selectedService, urlKeyword]);

    const chartEntries = useMemo(() => {
        if (!campaignInsights.length) {
            return [];
        }
        return campaignInsights.map((item, index) => {
            const value = parseNumber(item.spend) ?? 0;
            // Hỗ trợ cả date_start (Meta Ads) và date (Google Ads)
            const dateString = item.date_start || item.date || null;
            const date = dateString ? new Date(dateString) : null;
            
            // Label hiển thị trên x-axis: format ngắn gọn "dd/MM" để không bị vỡ
            const label = date
                ? date.toLocaleDateString('vi-VN', {
                      day: '2-digit',
                      month: '2-digit',
                  })
                : `#${index + 1}`;
            
            // Tooltip hiển thị đầy đủ: "Thứ Hai, 15/11/2025"
            const tooltipLabel = date
                ? date.toLocaleDateString('vi-VN', {
                      weekday: 'long',
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                  })
                : label;
            
            return {
                label,
                value,
                tooltipLabel,
                date: date, // Lưu date object để có thể dùng sau nếu cần
            };
        });
    }, [campaignInsights]);

    const closeView = () => {
        setSelectedService(null);
        setAccounts([]);
        setCampaignsByAccount({});
        setSelectedAccountId(null);
        setAccountsError(null);
        setCampaignError(null);
        setSelectedCampaign(null);
        setCampaignDetail(null);
        setCampaignDetailError(null);
        setCampaignInsights([]);
        setCampaignInsightsError(null);
        setCampaignInsightsLoading(false);
    };

    const extractData = (payload: any) => {
        if (!payload) return [];
        if (Array.isArray(payload.data)) {
            return payload.data;
        }
        if (payload.data?.data) {
            return payload.data.data;
        }
        return [];
    };

    const handleViewService = async (service: ServiceOrder) => {
        setSelectedService(service);
        setAccounts([]);
        setCampaignsByAccount({});
        setSelectedAccountId(null);
        await loadAccounts(service);
    };

    const loadAccounts = async (service: ServiceOrder) => {
        setAccountsLoading(true);
        setAccountsError(null);
        try {
            const platform = service.package?.platform;
            const apiPath = platform === _PlatformType.META 
                ? `/meta/${service.id}/accounts`
                : platform === _PlatformType.GOOGLE
                ? `/google-ads/${service.id}/accounts`
                : null;
            
            if (!apiPath) {
                setAccountsError(t('service_management.unsupported_platform'));
                return;
            }
            
            const response = await axios.get(apiPath, { params: { per_page: 20 } });
            const items = extractData(response.data?.data);
            setAccounts(items as AdAccount[]);
        } catch (error: any) {
            setAccountsError(error?.response?.data?.message || t('service_management.accounts_error'));
        } finally {
            setAccountsLoading(false);
        }
    };

    const loadCampaigns = async (account: AdAccount) => {
        if (!selectedService) return;
        
        const platform = selectedService.package?.platform;
        setSelectedAccountId(account.id);
        setCampaignError(null);
        setCampaignLoadingId(account.id);
        setSelectedCampaign(null);
        setCampaignDetail(null);
        
        try {
            let apiPath: string;
            if (platform === _PlatformType.GOOGLE) {
                apiPath = `/google-ads/${selectedService.id}/${account.id}/campaigns`;
            } else {
                apiPath = `/meta/${selectedService.id}/${account.id}/campaigns`;
            }
            
            const response = await axios.get(apiPath, {
                params: { per_page: 25 },
            });
            const items = extractData(response.data?.data);
            setCampaignsByAccount((prev) => ({
                ...prev,
                [account.id]: items,
            }));
        } catch (error: any) {
            setCampaignError(error?.response?.data?.message || t('service_management.campaigns_error'));
        } finally {
            setCampaignLoadingId(null);
        }
    };

    const loadCampaignInsights = async (campaign: Campaign, preset: 'last_7d' | 'last_30d') => {
        if (!selectedService) return;
        const platform = selectedService.package?.platform;
        setCampaignInsightsLoading(true);
        setCampaignInsightsError(null);
        try {
            const apiPath = platform === _PlatformType.GOOGLE
                ? `/google-ads/${selectedService.id}/${campaign.id}/detail-campaign-insight`
                : `/meta/${selectedService.id}/${campaign.id}/detail-campaign-insight`;
            
            const response = await axios.get(apiPath, { params: { date_preset: preset } });
            const payload = response.data?.data;
            const rawItems: any[] = Array.isArray(payload?.data)
                ? (payload.data as any[])
                : Array.isArray(payload)
                    ? (payload as any[])
                    : [];
            // Normalize dữ liệu: convert integer cpc/cpm thành float
            const items: CampaignDailyInsight[] = rawItems.map(normalizeInsightItem);
            setCampaignInsights(items);
        } catch (error: any) {
            setCampaignInsightsError(error?.response?.data?.message || t('service_management.campaign_insight_error'));
            setCampaignInsights([]);
        } finally {
            setCampaignInsightsLoading(false);
        }
    };

    const loadCampaignDetail = async (campaign: Campaign) => {
        if (!selectedService) return;
        const platform = selectedService.package?.platform;
        setSelectedCampaign(campaign);
        setCampaignInsights([]);
        setCampaignInsightsError(null);
        setCampaignDetailLoading(true);
        setCampaignDetailError(null);
        try {
            const apiPath = platform === _PlatformType.GOOGLE
                ? `/google-ads/${selectedService.id}/${campaign.id}/detail-campaign`
                : `/meta/${selectedService.id}/${campaign.id}/detail-campaign`;
            
            const response = await axios.get(apiPath);
            if (response.data?.data) {
                setCampaignDetail(response.data.data as CampaignDetail);
            }
            await loadCampaignInsights(campaign, insightPreset);
        } catch (error: any) {
            setCampaignDetailError(error?.response?.data?.message || t('service_management.campaign_detail_error'));
        } finally {
            setCampaignDetailLoading(false);
        }
    };

    const handleInsightPresetChange = (value: 'last_7d' | 'last_30d') => {
        setInsightPreset(value);
        if (selectedCampaign) {
            loadCampaignInsights(selectedCampaign, value);
        }
    };

    const refreshCurrentCampaign = async () => {
        if (!selectedService || !selectedAccountId) return;
        const account = accounts.find((acc) => acc.id === selectedAccountId);
        if (account) {
            await loadCampaigns(account);
        }
        if (selectedCampaign) {
            await loadCampaignDetail(selectedCampaign);
        }
    };

    const refreshCampaignListOnly = async () => {
        if (!selectedService || !selectedAccountId) return;
        const account = accounts.find((acc) => acc.id === selectedAccountId);
        if (account) {
            await loadCampaigns(account);
        }
    };

    const getBadgeClassName = (severity?: StatusSeverity | null) => {
        switch (severity) {
            case 'success':
                return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
            case 'warning':
                return 'bg-amber-50 text-amber-700 border border-amber-100';
            case 'error':
                return 'bg-red-50 text-red-700 border border-red-100';
            default:
                return 'bg-muted text-foreground border border-transparent';
        }
    };

    const getTextSeverityClassName = (severity?: StatusSeverity | null) => {
        switch (severity) {
            case 'success':
                return 'text-emerald-600';
            case 'warning':
                return 'text-amber-600';
            case 'error':
                return 'text-red-600';
            default:
                return 'text-muted-foreground';
        }
    };

    const renderStatusBadge = (label?: string | null, severity?: StatusSeverity | null) => {
        if (!label) {
            return null;
        }
        return (
            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${getBadgeClassName(severity)}`}>
                {label}
            </span>
        );
    };

    const accountIssueSummary = useMemo(() => {
        return accounts.reduce(
            (acc, item) => {
                if (item.status_severity === 'error') {
                    acc.error += 1;
                } else if (item.status_severity === 'warning') {
                    acc.warning += 1;
                }
                return acc;
            },
            { error: 0, warning: 0 }
        );
    }, [accounts]);

    const campaignsOfSelectedAccount = useMemo(() => {
        if (!selectedAccountId) {
            return [] as Campaign[];
        }
        return campaignsByAccount[selectedAccountId] || [];
    }, [selectedAccountId, campaignsByAccount]);

    const campaignIssueSummary = useMemo(() => {
        return campaignsOfSelectedAccount.reduce(
            (acc, item) => {
                if (item.status_severity === 'error') {
                    acc.error += 1;
                } else if (item.status_severity === 'warning') {
                    acc.warning += 1;
                }
                return acc;
            },
            { error: 0, warning: 0 }
        );
    }, [campaignsOfSelectedAccount]);

    const renderConfigInfo = (service: ServiceOrder) => {
        const config = service.config_account || {};
        return (
            <div className="text-sm text-muted-foreground space-y-1">
                {config.meta_email && (
                    <div>
                        <span className="font-medium text-foreground">{t('service_management.meta_email')}:</span>{' '}
                        {config.meta_email}
                    </div>
                )}
                {config.display_name && (
                    <div>
                        <span className="font-medium text-foreground">{t('service_management.display_name')}:</span>{' '}
                        {config.display_name}
                    </div>
                )}
                {config.bm_id && (
                    <div>
                        <span className="font-medium text-foreground">{t('service_management.bm_id')}:</span>{' '}
                        {config.bm_id}
                    </div>
                )}
            </div>
        );
    };

    const renderServiceList = () => {
        if (services.length === 0) {
            return (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        {t('service_management.empty')}
                    </CardContent>
                </Card>
            );
        }

        return (
            <div className="grid gap-4">
                {services.map((service) => (
                    <Card key={service.id}>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-lg">{service.package?.name || 'Service'}</CardTitle>
                                <CardDescription>
                                    {t('service_management.created_at', {
                                        date: service.created_at
                                            ? new Date(service.created_at).toLocaleString()
                                            : '--',
                                    })}
                                </CardDescription>
                            </div>
                            <Badge variant="secondary">{service.package?.platform_label}</Badge>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {renderConfigInfo(service)}
                            <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                <div>
                                    <span className="font-medium text-foreground">
                                        {t('service_management.total_budget')}:
                                    </span>{' '}
                                    {(() => {
                                        const budget = service.budget;
                                        if (!budget) {
                                            return <span className="text-muted-foreground">{t('service_orders.table.budget_unlimited')}</span>;
                                        }
                                        const budgetValue = parseFloat(budget);
                                        if (Number.isNaN(budgetValue)) {
                                            return <span className="text-muted-foreground">-</span>;
                                        }
                                        if (budgetValue === 0) {
                                            return <span className="text-muted-foreground">{t('service_orders.table.budget_unlimited')}</span>;
                                        }
                                        return <span className="text-muted-foreground">{formatCurrency(budget)}</span>;
                                    })()}
                                </div>
                                <div>
                                    <span className="font-medium text-foreground">
                                        {t('service_management.topup_amount', { defaultValue: 'Số tiền top-up' })}:
                                    </span>{' '}
                                    {formatCurrency(service.config_account?.top_up_amount ?? 0)}
                                </div>
                            </div>
                            <Button onClick={() => handleViewService(service)}>
                                {t('service_management.view_campaigns')}
                            </Button>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    };

    const renderCampaignView = () => {
        if (!selectedService) return null;

        const issueCampaigns = campaignsOfSelectedAccount.filter(
            (campaign) => campaign.status_severity && campaign.status_severity !== 'success'
        );

        const renderCampaignList = (list: Campaign[]) => {
            if (!list.length) {
                return (
                    <p className="text-sm text-muted-foreground">
                        {t('service_management.campaigns_empty')}
                    </p>
                );
            }

            return (
                <div className="space-y-3">
                    {list.map((campaign) => {
                        const campaignBadge = renderStatusBadge(
                            campaign.status_label ?? campaign.effective_status,
                            campaign.status_severity
                        );
                        return (
                        <div
                            key={campaign.id}
                            className={`rounded border p-3 space-y-2 cursor-pointer transition-colors ${
                                selectedCampaign?.id === campaign.id ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'
                            }`}
                            onClick={() => loadCampaignDetail(campaign)}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex-1 min-w-0">
                                    <p className="font-semibold text-sm">{campaign.name || campaign.campaign_id}</p>
                                    <p className="text-xs text-muted-foreground mt-1">ID: {campaign.campaign_id}</p>
                                    {campaignBadge && <div className="mt-2">{campaignBadge}</div>}
                                </div>
                            </div>
                            <div className="text-xs text-muted-foreground space-y-1">
                                {campaign.objective && (
                                    <div>
                                        <span className="font-medium">{t('service_management.objective')}:</span> {campaign.objective}
                                    </div>
                                )}
                                {campaign.daily_budget && (
                                    <div>
                                        <span className="font-medium">{t('service_management.daily_budget')}:</span> {campaign.daily_budget}
                                    </div>
                                )}
                                {selectedService?.package?.platform !== _PlatformType.GOOGLE && (
                                    <>
                                        <div>
                                            <span className="font-medium">{t('service_management.start_time')}:</span>{' '}
                                            {campaign.start_time ? new Date(campaign.start_time).toLocaleString() : '--'}
                                        </div>
                                        {campaign.stop_time && (
                                            <div>
                                                <span className="font-medium">{t('service_management.stop_time')}:</span>{' '}
                                                {new Date(campaign.stop_time).toLocaleString()}
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                            {campaign.status_severity && campaign.status_severity !== 'success' && (
                                <p className={`text-xs mt-1 ${getTextSeverityClassName(campaign.status_severity)}`}>
                                    {t('service_management.campaign_issue_hint')}
                                </p>
                            )}
                        </div>
                        );
                    })}
                </div>
            );
        };

        const accountAlertDescriptionKey =
            accountIssueSummary.error > 0
                ? 'service_management.account_issue_description_full'
                : 'service_management.account_issue_description_warning';

        const showCampaignWarningOnly = campaignIssueSummary.error === 0 && campaignIssueSummary.warning > 0;
        const accountAlertVariant: 'default' | 'destructive' = accountIssueSummary.error ? 'destructive' : 'default';
        const accountAlertClassName = accountIssueSummary.error ? undefined : 'border-amber-200 bg-amber-50 text-amber-800';

        return (
            <div className="space-y-6">
                <div className="sm:flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">{t('service_management.dialog_title')}</h2>
                        <p className="text-muted-foreground sm:mb-0 mb-4">
                            {selectedService?.package?.name} •{' '}
                            {t('service_management.dialog_platform', {
                                platform: selectedService?.package?.platform_label,
                            })}
                        </p>
                    </div>
                    <Button variant="outline" onClick={closeView}>
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        {t('service_management.back_to_services')}
                    </Button>
                </div>

                {accountsError && (
                    <Alert variant="destructive">
                        <AlertTitle>{t('service_management.accounts_error_title')}</AlertTitle>
                        <AlertDescription>{accountsError}</AlertDescription>
                    </Alert>
                )}

                {(accountIssueSummary.error > 0 || accountIssueSummary.warning > 0) && (
                    <Alert variant={accountAlertVariant} className={accountAlertClassName}>
                        <AlertTitle>{t('service_management.account_issue_title')}</AlertTitle>
                        <AlertDescription>
                            {t(accountAlertDescriptionKey, {
                                error: accountIssueSummary.error,
                                warning: accountIssueSummary.warning,
                            })}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-3">
                        <h3 className="font-semibold flex items-center gap-2">
                            <Radio className="h-4 w-4 text-primary" />
                            {t('service_management.accounts')}
                        </h3>
                        <ScrollArea className="h-[400px] rounded-xl border bg-white/90 p-4 shadow-sm">
                            {accountsLoading ? (
                                <div className="flex items-center justify-center py-10 text-muted-foreground">
                                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                    {t('service_management.loading')}
                                </div>
                            ) : accounts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('service_management.accounts_empty')}
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {accounts.map((account) => {
                                        const accountBadge = renderStatusBadge(account.status_label, account.status_severity);
                                        return (
                                            <div
                                                key={account.id}
                                                className={`rounded-xl border bg-white p-4 shadow-sm cursor-pointer transition-colors ${
                                                    selectedAccountId === account.id
                                                        ? 'border-primary ring-2 ring-primary/30'
                                                        : 'hover:border-primary/40'
                                                }`}
                                                onClick={() => loadCampaigns(account)}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex-1 min-w-0">
                                                        <p className="font-semibold text-sm">
                                                            {account.account_name || account.account_id}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground mt-1">
                                                            ID: {account.account_id}
                                                        </p>
                                                        {accountBadge && <div className="mt-2">{accountBadge}</div>}
                                                    </div>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            loadCampaigns(account);
                                                        }}
                                                        disabled={campaignLoadingId === account.id}
                                                    >
                                                        {campaignLoadingId === account.id && (
                                                            <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                                        )}
                                                        {t('service_management.load_campaigns')}
                                                    </Button>
                                                </div>
                                                <div className="text-xs text-muted-foreground mt-2 space-y-1">
                                                    {account.currency && (
                                                        <div>
                                                            <span className="font-medium">{t('service_management.currency')}:</span> {account.currency}
                                                        </div>
                                                    )}
                                                    {'balance' in account && account.balance && (
                                                        <div>
                                                            <span className="font-medium">{t('service_management.balance')}:</span> {account.balance}
                                                        </div>
                                                    )}
                                                    {'time_zone' in account && account.time_zone && (
                                                        <div>
                                                            <span className="font-medium">{t('service_management.time_zone')}:</span> {account.time_zone}
                                                        </div>
                                                    )}
                                                    {'primary_email' in account && account.primary_email && (
                                                        <div>
                                                            <span className="font-medium">{t('service_management.primary_email')}:</span> {account.primary_email}
                                                        </div>
                                                    )}
                                                </div>
                                                {'balance_exhausted' in account && account.balance_exhausted && (
                                                    <Alert variant="destructive" className="mt-2 py-2">
                                                        <AlertDescription className="text-xs">
                                                            {t('service_management.balance_exhausted_warning')}
                                                        </AlertDescription>
                                                    </Alert>
                                                )}
                                                {(account.disable_reason || account.status_message) && (
                                                    <p
                                                        className={`text-xs mt-2 ${getTextSeverityClassName(
                                                            account.disable_reason_severity || account.status_severity
                                                        )}`}
                                                    >
                                                        {account.disable_reason || account.status_message}
                                                    </p>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </ScrollArea>
                    </div>

                    <div className="space-y-3">
                        <h3 className="font-semibold">{t('service_management.campaigns')}</h3>
                        {campaignError && (
                            <Alert variant="destructive">
                                <AlertTitle>{t('service_management.campaigns_error_title')}</AlertTitle>
                                <AlertDescription>{campaignError}</AlertDescription>
                            </Alert>
                        )}
                        {selectedAccountId ? (
                            campaignLoadingId === selectedAccountId ? (
                                <div className="h-[400px] border rounded-xl bg-white p-4 flex items-center justify-center text-muted-foreground shadow-sm">
                                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                    {t('service_management.loading')}
                                </div>
                            ) : (
                                <>
                                    {campaignIssueSummary.error > 0 && (
                                        <Alert variant="destructive">
                                            <AlertTitle>{t('service_management.campaign_issue_title')}</AlertTitle>
                                            <AlertDescription>
                                                {t('service_management.campaign_issue_description', {
                                                    error: campaignIssueSummary.error,
                                                })}
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                    {showCampaignWarningOnly && (
                                        <Alert variant="default" className="border-amber-200 bg-amber-50 text-amber-800">
                                            <AlertTitle>{t('service_management.campaign_issue_warning_title')}</AlertTitle>
                                            <AlertDescription>
                                                {t('service_management.campaign_issue_warning_description', {
                                                    warning: campaignIssueSummary.warning,
                                                })}
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                    <Tabs defaultValue="all" className="bg-white rounded-xl border shadow-sm p-4">
                                        <TabsList className="grid grid-cols-2">
                                            <TabsTrigger value="all">
                                                {t('service_management.campaign_tab_all', { count: campaignsOfSelectedAccount.length })}
                                            </TabsTrigger>
                                            <TabsTrigger value="issues" disabled={!issueCampaigns.length}>
                                                {t('service_management.campaign_tab_issue', { count: issueCampaigns.length })}
                                            </TabsTrigger>
                                        </TabsList>
                                        <TabsContent value="all">
                                            <ScrollArea className="h-[360px] border rounded-xl bg-white p-3 shadow-inner">
                                                {renderCampaignList(campaignsOfSelectedAccount)}
                                            </ScrollArea>
                                        </TabsContent>
                                        <TabsContent value="issues">
                                            <ScrollArea className="h-[360px] border rounded-xl bg-white p-3 shadow-inner">
                                                {issueCampaigns.length ? (
                                                    renderCampaignList(issueCampaigns)
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">
                                                        {t('service_management.campaign_issue_empty')}
                                                    </p>
                                                )}
                                            </ScrollArea>
                                        </TabsContent>
                                    </Tabs>
                                </>
                            )
                        ) : (
                            <div className="text-sm text-muted-foreground border rounded-xl bg-white/80 p-4 shadow-sm">
                                {t('service_management.select_account_hint')}
                            </div>
                        )}
                    </div>
                </div>

                {selectedCampaign && (
                    <div className="mt-6">
                        <Separator className="mb-6" />
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between">
                                    <span>{t('service_management.campaign_detail')}</span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setSelectedCampaign(null);
                                            setCampaignDetail(null);
                                            setCampaignInsights([]);
                                            setCampaignInsightsError(null);
                                        }}
                                    >
                                        <ArrowLeft className="h-4 w-4 mr-2" />
                                        {t('common.back')}
                                    </Button>
                                </CardTitle>
                                <CardDescription>
                                    {selectedCampaign.name || selectedCampaign.campaign_id}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {campaignDetailLoading ? (
                                    <div className="flex items-center justify-center py-10">
                                        <Loader2 className="h-6 w-6 animate-spin mr-2" />
                                        {t('service_management.loading')}
                                    </div>
                                ) : campaignDetailError ? (
                                    <Alert variant="destructive">
                                        <AlertTitle>{t('service_management.campaign_detail_error')}</AlertTitle>
                                        <AlertDescription>{campaignDetailError}</AlertDescription>
                                    </Alert>
                                ) : campaignDetail ? (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div className="p-4 bg-muted/50 rounded-lg">
                                                <div className="text-sm text-muted-foreground">{t('service_management.today_spend')}</div>
                                                <div className="lg:text-2xl text-lg font-bold mt-1">
                                                    {formatCurrency(campaignDetail.today_spend)}
                                                </div>
                                            </div>
                                            <div className="p-4 bg-muted/50 rounded-lg">
                                                <div className="text-sm text-muted-foreground">{t('service_management.total_spend')}</div>
                                                <div className="lg:text-2xl text-lg font-bold mt-1">
                                                    {formatCurrency(campaignDetail.total_spend)}
                                                </div>
                                            </div>
                                            <div className="p-4 bg-muted/50 rounded-lg">
                                                <div className="text-sm text-muted-foreground">CPC</div>
                                                <div className="lg:text-2xl text-lg font-bold mt-1">
                                                    {formatCurrency(campaignDetail.cpc_avg)}
                                                </div>
                                            </div>
                                            <div className="p-4 bg-muted/50 rounded-lg">
                                                <div className="text-sm text-muted-foreground">ROAS</div>
                                                <div className="lg:text-2xl text-lg font-bold mt-1">
                                                    {(() => {
                                                        const value = formatNumber(campaignDetail.roas_avg, {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        });
                                                        return value === '--' ? value : `${value}x`;
                                                    })()}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {Object.entries(campaignDetail.insight || {}).map(([key, value]) => {
                                                const percentNumber = parseNumber(value?.percent_change);
                                                const percentText = formatPercentChange(value?.percent_change);
                                                const PercentIcon = percentNumber === null ? null : percentNumber >= 0 ? TrendingUp : TrendingDown;
                                                const percentColor =
                                                    percentNumber === null
                                                        ? 'text-muted-foreground'
                                                        : percentNumber >= 0
                                                            ? 'text-green-500'
                                                            : 'text-red-500';

                                                return (
                                                    <Card key={key}>
                                                        <CardHeader className="pb-3">
                                                            <CardTitle className="text-base capitalize">{key}</CardTitle>
                                                        </CardHeader>
                                                        <CardContent className="space-y-2">
                                                            <div className="flex justify-between items-center">
                                                                <span className="text-sm text-muted-foreground">{t('service_management.today')}</span>
                                                                <span className="font-semibold">{formatNumber(value?.today)}</span>
                                                            </div>
                                                            <div className="flex justify-between items-center">
                                                                <span className="text-sm text-muted-foreground">{t('service_management.total')}</span>
                                                                <span className="font-semibold">{formatNumber(value?.total)}</span>
                                                            </div>
                                                            <div className="flex justify-between items-center pt-2 border-t">
                                                                <span className="text-sm text-muted-foreground">{t('service_management.change')}</span>
                                                                <div className="flex items-center gap-1">
                                                                    {PercentIcon && <PercentIcon className={`h-4 w-4 ${percentColor}`} />}
                                                                    <span className={`font-semibold ${percentColor}`}>{percentText}</span>
                                                                </div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                );
                                            })}
                                        </div>

                                        {/* Hành động chiến dịch */}
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                                            {/*
                                             * Xác định trạng thái hiện tại của campaign để bật/tắt nút
                                             */}
                                            {(() => {
                                                // Lấy status/effective_status từ chi tiết (ưu tiên) hoặc từ selectedCampaign
                                                const rawStatus =
                                                    campaignDetail?.effective_status ||
                                                    campaignDetail?.status ||
                                                    selectedCampaign?.effective_status ||
                                                    selectedCampaign?.status ||
                                                    null;
                                                const normalizedStatus = rawStatus
                                                    ? String(rawStatus).toUpperCase()
                                                    : null;
                                                const isDeleted =
                                                    normalizedStatus === 'DELETED' ||
                                                    normalizedStatus === 'ARCHIVED';
                                                const isPaused = normalizedStatus === 'PAUSED';
                                                const pauseDisabled =
                                                    !selectedCampaign || pauseSubmitting || isDeleted || isPaused;
                                                const resumeDisabled =
                                                    !selectedCampaign || resumeSubmitting || isDeleted || !isPaused;
                                                const endDisabled = !selectedCampaign || endSubmitting || isDeleted;
                                                const budgetDisabled = !selectedCampaign || budgetSubmitting || isDeleted;

                                                return (
                                                    <>
                                            {isPaused ? (
                                                <Button
                                                    variant="outline"
                                                    className="h-11 rounded-full px-8"
                                                    onClick={() => setResumeDialogOpen(true)}
                                                    disabled={resumeDisabled}
                                                >
                                                    {t('service_management.campaign_resume')}
                                                </Button>
                                            ) : (
                                                <Button
                                                    variant="outline"
                                                    className="h-11 rounded-full px-8"
                                                    onClick={() => setPauseDialogOpen(true)}
                                                    disabled={pauseDisabled}
                                                >
                                                    {t('service_management.campaign_pause')}
                                                </Button>
                                            )}
                                            <Button
                                                variant="destructive"
                                                className="h-11 rounded-full px-8"
                                                onClick={() => setEndDialogOpen(true)}
                                                disabled={endDisabled}
                                            >
                                                {t('service_management.campaign_end')}
                                            </Button>
                                            <Button
                                                className="h-11 rounded-full px-8"
                                                variant="default"
                                                onClick={async () => {
                                                    setBudgetDialogOpen(true);

                                                    // Chỉ lấy số dư ví cho role Agency/Customer
                                                    if (isAgencyOrCustomer && walletBalance === null && !walletBalanceLoading) {
                                                        try {
                                                            setWalletBalanceLoading(true);
                                                            const response = await axios.get(wallet_me_json().url);
                                                            const balance = response?.data?.data?.balance;
                                                            setWalletBalance(
                                                                typeof balance === 'number'
                                                                    ? balance
                                                                    : parseNumber(balance)
                                                            );
                                                        } catch (e) {
                                                            // Nếu lỗi thì vẫn cho mở dialog, chỉ không hiển thị số dư
                                                            setWalletBalance(null);
                                                        } finally {
                                                            setWalletBalanceLoading(false);
                                                        }
                                                    }
                                                }}
                                                disabled={budgetDisabled}
                                            >
                                                {t('service_management.campaign_update_budget')}
                                            </Button>
                                                    </>
                                                );
                                            })()}
                                        </div>
                                    </div>
                                ) : null}
                            </CardContent>
                        </Card>
                        {renderSpendChart()}

                        {/* Dialog cập nhật ngân sách */}
                        <Dialog open={budgetDialogOpen} onOpenChange={setBudgetDialogOpen}>
                            <DialogContent>
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <span>{t('service_management.campaign_update_budget')}</span>
                                    {selectedService?.package?.platform === _PlatformType.META && (
                                        <span
                                            className="text-xs text-muted-foreground"
                                            title={t(
                                                'service_management.campaign_update_budget_help_meta_tooltip',
                                            )}
                                        >
                                            ⓘ
                                        </span>
                                    )}
                                    {selectedService?.package?.platform === _PlatformType.GOOGLE && (
                                        <span
                                            className="text-xs text-muted-foreground"
                                            title={t(
                                                'service_management.campaign_update_budget_help_google_tooltip',
                                            )}
                                        >
                                            ⓘ
                                        </span>
                                    )}
                                </DialogTitle>
                                <DialogDescription>
                                    {selectedService?.package?.platform === _PlatformType.META &&
                                        t('service_management.campaign_update_budget_help_meta')}
                                    {selectedService?.package?.platform === _PlatformType.GOOGLE &&
                                        t('service_management.campaign_update_budget_help_google')}
                                    {!selectedService?.package?.platform &&
                                        t('service_management.campaign_update_budget_description')}
                                </DialogDescription>
                            </DialogHeader>
                                <div className="space-y-4 pt-2">
                                    {isAgencyOrCustomer && (walletBalanceLoading || walletBalance !== null) && (
                                        <div className="text-sm text-muted-foreground">
                                            {walletBalanceLoading
                                                ? t('service_management.campaign_update_budget_wallet_balance_loading')
                                                : walletBalance !== null
                                                    ? t('service_management.campaign_update_budget_wallet_balance', {
                                                        balance: walletBalance.toLocaleString(undefined, {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        }),
                                                    })
                                                    : t('service_management.campaign_update_budget_wallet_balance_error')}
                                        </div>
                                    )}
                                    <div className="space-y-1">
                                        <Label htmlFor="budget-amount">
                                            {t('service_management.campaign_update_budget_amount_label')}
                                        </Label>
                                        <Input
                                            id="budget-amount"
                                            type="number"
                                            min={0}
                                            step="0.01"
                                            value={budgetAmount}
                                            onChange={(e) => setBudgetAmount(e.target.value)}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {t('service_management.campaign_update_budget_min_hint', {
                                                amount: 100,
                                            })}
                                        </p>
                                    </div>
                                    {isAgencyOrCustomer && (
                                        <div className="space-y-1">
                                            <Label htmlFor="budget-wallet-password">
                                                {t(
                                                    'service_management.campaign_update_budget_wallet_password_label',
                                                )}
                                            </Label>
                                            <Input
                                                id="budget-wallet-password"
                                                type="password"
                                                value={budgetWalletPassword}
                                                onChange={(e) => setBudgetWalletPassword(e.target.value)}
                                            />
                                        </div>
                                    )}
                                </div>
                                <DialogFooter className="pt-4">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            if (!budgetSubmitting) {
                                                setBudgetDialogOpen(false);
                                            }
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button
                                        onClick={async () => {
                                            if (!budgetAmount || Number(budgetAmount) <= 0) {
                                                toast.error(
                                                    t('common_validation.amount_required') || 'Amount is invalid',
                                                );
                                                return;
                                            }

                                            const amountNumber = Number(budgetAmount);
                                            if (amountNumber < 100) {
                                                toast.error(
                                                    t('service_management.campaign_update_budget_min_error', {
                                                        amount: 100,
                                                    }),
                                                );
                                                return;
                                            }

                                            // Với Agency/Customer thì bắt buộc nhập mật khẩu ví
                                            if (isAgencyOrCustomer && !budgetWalletPassword) {
                                                toast.error(
                                                    t(
                                                        'service_management.campaign_update_budget_wallet_password_required',
                                                    ),
                                                );
                                                return;
                                            }

                                            try {
                                                setBudgetSubmitting(true);
                                                const platformType = selectedService?.package?.platform ?? null;
                                                if (!selectedService || !selectedCampaign || !platformType) {
                                                    toast.error(t('service_management.campaign_not_selected'));
                                                    return;
                                                }

                                                const amountNumber = Number(budgetAmount);

                                                if (platformType === _PlatformType.META) {
                                                    await axios.post(
                                                        `/meta/${selectedService.id}/${selectedCampaign.id}/spend-cap`,
                                                        {
                                                            amount: amountNumber,
                                                        },
                                                    );
                                                } else if (platformType === _PlatformType.GOOGLE) {
                                                    await axios.post(
                                                        `/google-ads/${selectedService.id}/${selectedCampaign.id}/budget`,
                                                        {
                                                            amount: amountNumber,
                                                        },
                                                    );
                                                } else {
                                                    toast.error(t('service_management.unsupported_platform'));
                                                    return;
                                                }

                                                toast.success(
                                                    t('service_management.campaign_update_budget_success', {
                                                        amount: amountNumber.toLocaleString(undefined, {
                                                            minimumFractionDigits: 0,
                                                            maximumFractionDigits: 2,
                                                        }),
                                                    }),
                                                );

                                                // Reload lại dữ liệu để UI cập nhật ngay
                                                await refreshCurrentCampaign();
                                                setBudgetDialogOpen(false);
                                                setBudgetAmount('');
                                                setBudgetWalletPassword('');
                                            } catch (error: any) {
                                                const message =
                                                    error?.response?.data?.message ||
                                                    t('service_management.campaign_update_budget_insufficient_balance');
                                                toast.error(message);
                                            } finally {
                                                setBudgetSubmitting(false);
                                            }
                                        }}
                                        disabled={budgetSubmitting}
                                    >
                                        {budgetSubmitting
                                            ? t('common.processing')
                                            : t('service_management.campaign_update_budget_submit')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        {/* Dialog Tạm dừng chiến dịch */}
                        <Dialog open={pauseDialogOpen} onOpenChange={setPauseDialogOpen}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{t('service_management.campaign_pause')}</DialogTitle>
                                    <DialogDescription>
                                        {t('service_management.campaign_pause_description')}
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    <Alert>
                                        <AlertDescription>
                                            {t('service_management.campaign_pause_warning')}
                                        </AlertDescription>
                                    </Alert>
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            if (!pauseSubmitting) {
                                                setPauseDialogOpen(false);
                                            }
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button
                                        onClick={async () => {
                                            try {
                                                setPauseSubmitting(true);
                                                const platformType = selectedService?.package?.platform ?? null;
                                                if (!selectedService || !selectedCampaign || !platformType) {
                                                    toast.error(t('service_management.campaign_not_selected'));
                                                    return;
                                                }

                                                if (platformType === _PlatformType.META) {
                                                    await axios.post(
                                                        `/meta/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'PAUSED' },
                                                    );
                                                } else if (platformType === _PlatformType.GOOGLE) {
                                                    await axios.post(
                                                        `/google-ads/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'PAUSED' },
                                                    );
                                                } else {
                                                    toast.error(t('service_management.unsupported_platform'));
                                                    return;
                                                }

                                                toast.success(t('service_management.campaign_pause_success'));
                                                // Reload lại dữ liệu để UI cập nhật ngay
                                                await refreshCurrentCampaign();
                                                setPauseDialogOpen(false);
                                            } catch (error: any) {
                                                const message =
                                                    error?.response?.data?.message ||
                                                    t('service_management.campaign_pause_error');
                                                toast.error(message);
                                            } finally {
                                                setPauseSubmitting(false);
                                            }
                                        }}
                                        disabled={pauseSubmitting}
                                    >
                                        {pauseSubmitting
                                            ? t('common.processing')
                                            : t('service_management.campaign_pause_confirm')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        {/* Dialog Tiếp tục chiến dịch */}
                        <Dialog open={resumeDialogOpen} onOpenChange={setResumeDialogOpen}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{t('service_management.campaign_resume')}</DialogTitle>
                                    <DialogDescription>
                                        {t('service_management.campaign_resume_description')}
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    <Alert>
                                        <AlertDescription>
                                            {t('service_management.campaign_resume_warning')}
                                        </AlertDescription>
                                    </Alert>
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            if (!resumeSubmitting) {
                                                setResumeDialogOpen(false);
                                            }
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button
                                        onClick={async () => {
                                            try {
                                                setResumeSubmitting(true);
                                                const platformType = selectedService?.package?.platform ?? null;
                                                if (!selectedService || !selectedCampaign || !platformType) {
                                                    toast.error(t('service_management.campaign_not_selected'));
                                                    return;
                                                }

                                                if (platformType === _PlatformType.META) {
                                                    await axios.post(
                                                        `/meta/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'ACTIVE' },
                                                    );
                                                } else if (platformType === _PlatformType.GOOGLE) {
                                                    await axios.post(
                                                        `/google-ads/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'ENABLED' },
                                                    );
                                                } else {
                                                    toast.error(t('service_management.unsupported_platform'));
                                                    return;
                                                }

                                                toast.success(t('service_management.campaign_resume_success'));
                                                // Reload lại dữ liệu để UI cập nhật ngay
                                                await refreshCurrentCampaign();
                                                setResumeDialogOpen(false);
                                            } catch (error: any) {
                                                const message =
                                                    error?.response?.data?.message ||
                                                    t('service_management.campaign_resume_error');
                                                toast.error(message);
                                            } finally {
                                                setResumeSubmitting(false);
                                            }
                                        }}
                                        disabled={resumeSubmitting}
                                    >
                                        {resumeSubmitting
                                            ? t('common.processing')
                                            : t('service_management.campaign_resume_confirm')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        {/* Dialog Kết thúc chiến dịch */}
                        <Dialog open={endDialogOpen} onOpenChange={setEndDialogOpen}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{t('service_management.campaign_end')}</DialogTitle>
                                    <DialogDescription>
                                        {t('service_management.campaign_end_description')}
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    <Alert variant="destructive">
                                        <AlertDescription>
                                            {t('service_management.campaign_end_warning')}
                                        </AlertDescription>
                                    </Alert>
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            if (!endSubmitting) {
                                                setEndDialogOpen(false);
                                            }
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={async () => {
                                            try {
                                                setEndSubmitting(true);
                                                const platformType = selectedService?.package?.platform ?? null;
                                                if (!selectedService || !selectedCampaign || !platformType) {
                                                    toast.error(t('service_management.campaign_not_selected'));
                                                    return;
                                                }

                                                if (platformType === _PlatformType.META) {
                                                    await axios.post(
                                                        `/meta/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'DELETED' },
                                                    );
                                                } else if (platformType === _PlatformType.GOOGLE) {
                                                    await axios.post(
                                                        `/google-ads/${selectedService.id}/${selectedCampaign.id}/status`,
                                                        { status: 'REMOVED' },
                                                    );
                                                } else {
                                                    toast.error(t('service_management.unsupported_platform'));
                                                    return;
                                                }

                                                toast.success(t('service_management.campaign_end_success'));
                                                setEndDialogOpen(false);

                                                // Sau khi kết thúc, reload danh sách campaign và clear detail
                                                await refreshCampaignListOnly();
                                                setSelectedCampaign(null);
                                                setCampaignDetail(null);
                                                setCampaignInsights([]);
                                            } catch (error: any) {
                                            const message =
                                                    error?.response?.data?.message ||
                                                    t('service_management.campaign_end_error');
                                                toast.error(message);
                                            } finally {
                                                setEndSubmitting(false);
                                            }
                                        }}
                                        disabled={endSubmitting}
                                    >
                                        {endSubmitting
                                            ? t('common.processing')
                                            : t('service_management.campaign_end_confirm')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                )}
            </div>
        );
    };

    const renderSpendChart = () => {
        if (!selectedCampaign) return null;
        const spendChangeNumber = parseNumber(campaignDetail?.insight?.spend?.percent_change);
        const spendChangeText = formatPercentChange(campaignDetail?.insight?.spend?.percent_change);
        const spendChangeColor =
            spendChangeNumber === null
                ? 'text-muted-foreground'
                : spendChangeNumber >= 0
                    ? 'text-green-600'
                    : 'text-red-600';

        return (
            <Card className="mt-4">
                <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <CardTitle>{t('service_management.spend_chart_title')}</CardTitle>
                        <CardDescription className={spendChangeColor}>
                            {spendChangeNumber === null
                                ? t('service_management.spend_chart_no_change')
                                : t('service_management.spend_chart_description', { percent: spendChangeText })}
                        </CardDescription>
                    </div>
                    <Select
                        value={insightPreset}
                        onValueChange={(value) => handleInsightPresetChange(value as 'last_7d' | 'last_30d')}
                        disabled={campaignInsightsLoading}
                    >
                        <SelectTrigger className="w-[140px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {insightPresetOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent>
                    {campaignInsightsError && (
                        <Alert variant="destructive" className="mb-4">
                            <AlertTitle>{t('service_management.campaign_insight_error')}</AlertTitle>
                            <AlertDescription>{campaignInsightsError}</AlertDescription>
                        </Alert>
                    )}

                    {campaignInsightsLoading ? (
                        <div className="flex items-center justify-center py-10 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin mr-2" />
                            {t('service_management.loading')}
                        </div>
                    ) : chartEntries.length === 0 ? (
                        <div className="text-sm text-muted-foreground py-6">
                            {t('service_management.spend_chart_empty')}
                        </div>
                    ) : (
                        <ChartContainer className="h-64 w-full">
                            <BarChart data={chartEntries} barCategoryGap="30%">
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis
                                    dataKey="label"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    angle={-45}
                                    textAnchor="end"
                                    height={60}
                                    style={{ fontSize: '11px' }}
                                />
                                <ChartTooltip
                                    labelFormatter={(label: string, payload?: any) => {
                                        const tooltipLabel = payload?.[0]?.payload?.tooltipLabel;
                                        return tooltipLabel ?? label;
                                    }}
                                    content={
                                        <ChartTooltipContent
                                            formatter={(value: ValueType | null | undefined) =>
                                                typeof value === 'number' ? formatCurrency(value) : value ?? '--'
                                            }
                                        />
                                    }
                                />
                                <Bar
                                    dataKey="value"
                                    name={t('service_management.spend_chart_series')}
                                    fill="hsl(var(--primary))"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ChartContainer>
                    )}
                </CardContent>
            </Card>
        );
    };

    return (
        <>
            <Head title={t('menu.service_management')} />
            <div className="space-y-6">
                {!selectedService ? (
                    <>
                        <div>
                            <h1 className="text-2xl font-semibold">{t('service_management.title')}</h1>
                            <p className="text-muted-foreground">{t('service_management.subtitle')}</p>
                        </div>
                        {renderServiceList()}
                        {services.length > 0 && <DataTablePagination paginator={paginator} />}
                    </>
                ) : (
                    renderCampaignView()
                )}
            </div>
        </>
    );
};

ServiceManagementIndex.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'menu.service_management' }]} children={page} />
);

export default ServiceManagementIndex;

