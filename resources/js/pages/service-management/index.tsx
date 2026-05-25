import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import axios from 'axios';
import {
    ArrowLeft,
    Loader2,
    Pause,
    Play,
    RefreshCw,
    Trash2,
    TrendingDown,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { useTranslation } from 'react-i18next';
import { Bar, BarChart, CartesianGrid, Cell, XAxis, YAxis } from 'recharts';
import { toast } from 'sonner';

import { DataTable } from '@/components/table/data-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TableCell, TableRow } from '@/components/ui/table';

import { _PlatformType, _UserRole } from '@/lib/types/constants';
import {
    formatCurrency,
    formatDateForQuery,
    formatMoney,
    formatNumber,
} from '@/lib/utils';
import BusinessManagerSearchForm from '@/pages/business-manager/components/search-form';
import type {
    BusinessManagerItem,
    BusinessManagerPagination,
    BusinessManagerStats,
    BusinessManagerTotals,
} from '@/pages/business-manager/types/type';
import { useSearchServiceManagement } from '@/pages/service-management/hooks/use-search';
import type {
    Campaign,
    CampaignDailyInsight,
    CampaignDetail,
    StatusSeverity,
} from '@/pages/service-management/types/types';

type ChildManagerOption = {
    id: string;
    name: string;
    parent_id: string;
};

type Props = {
    paginator: BusinessManagerPagination;
    stats: BusinessManagerStats;
    totals: BusinessManagerTotals;
    childManagers?: {
        meta?: ChildManagerOption[];
        google?: ChildManagerOption[];
    };
};

const getSeverityBadge = (severity?: StatusSeverity | null) => {
    switch (severity) {
        case 'error':
            return 'destructive' as const;
        case 'warning':
            return 'outline' as const;
        case 'success':
            return 'default' as const;
        default:
            return 'secondary' as const;
    }
};

const ServiceManagementIndex = ({
    paginator,
    stats,
    totals,
    childManagers,
}: Props) => {
    const { t, i18n } = useTranslation();
    const { auth } = usePage().props as any;
    const isAgencyOrCustomer =
        auth?.user?.role_id === _UserRole.AGENCY ||
        auth?.user?.role_id === _UserRole.CUSTOMER;
    const { query, setQuery, handleSearch, handleReset } =
        useSearchServiceManagement();

    const [selectedAccount, setSelectedAccount] =
        useState<BusinessManagerItem | null>(null);
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [campaignLoading, setCampaignLoading] = useState(false);
    const [campaignError, setCampaignError] = useState<string | null>(null);
    const [campaignDateRange, setCampaignDateRange] = useState<
        DateRange | undefined
    >();

    // Dành cho Chi tiết Chiến dịch
    const [selectedCampaign, setSelectedCampaign] = useState<Campaign | null>(
        null,
    );
    const [campaignDetail, setCampaignDetail] = useState<CampaignDetail | null>(
        null,
    );
    const [campaignDetailLoading, setCampaignDetailLoading] = useState(false);
    const [campaignDetailError, setCampaignDetailError] = useState<
        string | null
    >(null);

    // Insights/Biểu đồ
    const [campaignInsights, setCampaignInsights] = useState<
        CampaignDailyInsight[]
    >([]);
    const [campaignInsightsLoading, setCampaignInsightsLoading] =
        useState(false);
    const [campaignInsightsError, setCampaignInsightsError] = useState<
        string | null
    >(null);
    const [insightPreset, setInsightPreset] = useState<'last_7d' | 'last_30d'>(
        'last_7d',
    );

    // Dialog & Submit States cho Chiến dịch
    const [budgetDialogOpen, setBudgetDialogOpen] = useState(false);
    const [budgetAmount, setBudgetAmount] = useState('');
    const [budgetSubmitting, setBudgetSubmitting] = useState(false);
    const [budgetWalletPassword, setBudgetWalletPassword] = useState('');

    const [pauseDialogOpen, setPauseDialogOpen] = useState(false);
    const [pauseSubmitting, setPauseSubmitting] = useState(false);

    const [resumeDialogOpen, setResumeDialogOpen] = useState(false);
    const [resumeSubmitting, setResumeSubmitting] = useState(false);

    const [endDialogOpen, setEndDialogOpen] = useState(false);
    const [endSubmitting, setEndSubmitting] = useState(false);

    // Wallet balance cho Agency/Customer khi update budget
    const [walletBalance, setWalletBalance] = useState<number | null>(null);
    const [walletBalanceLoading, setWalletBalanceLoading] = useState(false);
    const [syncMetaSubmitting, setSyncMetaSubmitting] = useState(false);

    const selectedMetaBmId =
        query.platform === _PlatformType.META
            ? query.child_manager_id || query.manager_id
            : undefined;

    const lastSyncedAt = useMemo(() => {
        const dates = paginator.data
            .map((item) => item.last_synced_at)
            .filter(Boolean)
            .map((value) => new Date(value as string))
            .filter((date) => !Number.isNaN(date.getTime()))
            .sort((a, b) => b.getTime() - a.getTime());

        return dates[0]?.toISOString() ?? null;
    }, [paginator.data]);

    const handleSyncMetaInsights = useCallback(async () => {
        if (!selectedMetaBmId) {
            toast.error(
                t('service_management.sync_meta_select_bm', {
                    defaultValue: 'Vui lòng chọn BM/MCC con cần đồng bộ',
                }),
            );
            return;
        }

        setSyncMetaSubmitting(true);
        try {
            await axios.post('/service-management/sync-meta-insights', {
                bm_id: selectedMetaBmId,
            });
            toast.success(
                t('service_management.sync_meta_queued', {
                    defaultValue: 'Đã đưa yêu cầu đồng bộ Meta vào hàng đợi',
                }),
            );
        } catch (e: any) {
            toast.error(
                e?.response?.data?.message ||
                    t('service_management.sync_meta_failed', {
                        defaultValue: 'Không thể gửi yêu cầu đồng bộ Meta',
                    }),
            );
        } finally {
            setSyncMetaSubmitting(false);
        }
    }, [selectedMetaBmId, t]);

    const loadCampaigns = useCallback(
        async (
            accountArg?: BusinessManagerItem,
            dateRangeArg?: DateRange | null,
        ) => {
            const account = accountArg ?? selectedAccount;
            if (!account) return;

            const canLoadPlatformCampaigns =
                account?.platform === _PlatformType.META;
            if (!account?.service_user_id && !canLoadPlatformCampaigns) {
                setCampaignError(
                    t('service_management.account_not_assigned', {
                        defaultValue:
                            'Tài khoản này chưa được gán với user nào',
                    }),
                );
                return;
            }

            setSelectedAccount(account);
            setCampaignError(null);
            setCampaignLoading(true);
            setCampaigns([]);
            setSelectedCampaign(null);
            setCampaignDetail(null);

            try {
                const apiPath =
                    !account.service_user_id &&
                    account.platform === _PlatformType.META
                        ? `/meta/platform-accounts/${account.id}/campaigns`
                        : account.platform === _PlatformType.GOOGLE
                          ? `/google-ads/${account.service_user_id}/${account.id}/campaigns`
                          : `/meta/${account.service_user_id}/${account.id}/campaigns`;

                const dateRange =
                    dateRangeArg === undefined
                        ? campaignDateRange
                        : dateRangeArg;
                const filter: Record<string, string | undefined> = {};
                if (dateRange?.from && dateRange?.to) {
                    filter.start_date = formatDateForQuery(dateRange.from);
                    filter.end_date = formatDateForQuery(dateRange.to);
                }

                const response = await axios.get(apiPath, {
                    params: {
                        per_page: 50,
                        filter,
                    },
                });
                const payload = response.data?.data;
                const items: any[] = Array.isArray(payload?.data)
                    ? payload.data
                    : Array.isArray(payload)
                      ? payload
                      : [];
                setCampaigns(items as Campaign[]);
            } catch (e: any) {
                setCampaignError(
                    e?.response?.data?.message ||
                        t('service_management.campaigns_error', {
                            defaultValue: 'Không thể tải chiến dịch',
                        }),
                );
            } finally {
                setCampaignLoading(false);
            }
        },
        [campaignDateRange, selectedAccount, t],
    );

    const loadCampaignInsights = useCallback(
        async (campaignId: string, preset: 'last_7d' | 'last_30d') => {
            if (!selectedAccount?.service_user_id) return;

            setCampaignInsightsLoading(true);
            setCampaignInsightsError(null);
            try {
                const platformPrefix =
                    selectedAccount.platform === _PlatformType.GOOGLE
                        ? 'google-ads'
                        : 'meta';
                const response = await axios.get(
                    `/${platformPrefix}/${selectedAccount.service_user_id}/${campaignId}/detail-campaign-insight`,
                    { params: { date_preset: preset } },
                );
                setCampaignInsights(response.data?.data || []);
            } catch (e: any) {
                setCampaignInsightsError(
                    e?.response?.data?.message ||
                        t('service_management.campaign_insight_error'),
                );
            } finally {
                setCampaignInsightsLoading(false);
            }
        },
        [selectedAccount, t],
    );

    const loadCampaignDetail = useCallback(
        async (campaign: Campaign) => {
            if (!selectedAccount?.service_user_id) return;

            setSelectedCampaign(campaign);
            setCampaignDetail(null);
            setCampaignDetailError(null);
            setCampaignDetailLoading(true);

            try {
                const platformPrefix =
                    selectedAccount.platform === _PlatformType.GOOGLE
                        ? 'google-ads'
                        : 'meta';
                const response = await axios.get(
                    `/${platformPrefix}/${selectedAccount.service_user_id}/${campaign.id}/detail-campaign`,
                );
                setCampaignDetail(response.data?.data);

                // Tải luôn insight mặc định (7 ngày)
                await loadCampaignInsights(campaign.id, 'last_7d');
            } catch (e: any) {
                setCampaignDetailError(
                    e?.response?.data?.message ||
                        t('service_management.campaign_detail_error'),
                );
            } finally {
                setCampaignDetailLoading(false);
            }
        },
        [selectedAccount, t, loadCampaignInsights],
    );

    const handleInsightPresetChange = (value: 'last_7d' | 'last_30d') => {
        setInsightPreset(value);
        if (selectedCampaign) {
            loadCampaignInsights(selectedCampaign.id, value);
        }
    };

    const refreshCurrentCampaign = useCallback(async () => {
        if (selectedCampaign) {
            await loadCampaignDetail(selectedCampaign);
        }
    }, [selectedCampaign, loadCampaignDetail]);

    const refreshCampaignListOnly = useCallback(async () => {
        if (selectedAccount) {
            await loadCampaigns(selectedAccount);
        }
    }, [selectedAccount, loadCampaigns]);

    // Chuẩn hóa dữ liệu cho biểu đồ
    const chartEntries = useMemo(() => {
        return campaignInsights.map((day) => {
            const dateStr = day.date || day.date_start || '';
            let label = dateStr;
            let tooltipLabel = dateStr;

            if (dateStr) {
                try {
                    const d = new Date(dateStr);
                    label = `${d.getDate()}/${d.getMonth() + 1}`;
                    tooltipLabel = d.toLocaleDateString();
                } catch (e) {
                    /* ignore */
                }
            }

            return {
                label,
                tooltipLabel,
                value: Number(day.spend || 0),
            };
        });
    }, [campaignInsights]);

    const parseNumber = (val: any) => {
        if (typeof val === 'number') return val;
        const n = parseFloat(String(val));
        return Number.isNaN(n) ? null : n;
    };

    const formatAccountCurrency = (
        value: unknown,
        currency?: string | null,
    ) => {
        return formatMoney(value, currency || 'USD', i18n.language);
    };

    const formatTotalsSpend = () => {
        const totalsByCurrency = totals?.totals_by_currency ?? [];
        if (totalsByCurrency.length > 1) {
            return (
                <div className="space-y-1">
                    {totalsByCurrency.map((item) => (
                        <div key={item.currency}>
                            {formatAccountCurrency(
                                item.total_spend,
                                item.currency,
                            )}
                        </div>
                    ))}
                </div>
            );
        }

        const singleTotal = totalsByCurrency[0];
        return formatAccountCurrency(
            singleTotal?.total_spend ?? totals?.total_spend,
            singleTotal?.currency ?? totals?.currency,
        );
    };

    const getAccountStatusClassName = (
        severity?: string | null,
        label?: string | null,
    ) => {
        const normalizedLabel = (label ?? '').toLowerCase();
        if (severity === 'success') {
            return 'border-green-200 bg-green-100 text-green-700 hover:bg-green-100';
        }
        if (
            severity === 'warning' ||
            normalizedLabel.includes('nợ') ||
            normalizedLabel.includes('unsettled') ||
            normalizedLabel.includes('need to pay')
        ) {
            return 'border-orange-200 bg-orange-100 text-orange-700 hover:bg-orange-100';
        }
        if (severity === 'error') {
            return 'border-red-200 bg-red-100 text-red-700 hover:bg-red-100';
        }
        return '';
    };

    const formatDateTime = (value?: string | null) => {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('vi-VN');
    };

    const formatDateTimeFull = (value?: string | null) => {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const formatPercentChange = (val: any) => {
        const n = parseNumber(val);
        if (n === null) return '--';
        const sign = n >= 0 ? '+' : '';
        return `${sign}${n.toFixed(2)}%`;
    };

    const accountColumns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('service_management.account_name', {
                    defaultValue: 'Tài khoản quảng cáo',
                }),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <div className="truncate font-medium">
                            {row.original.account_name || '-'}
                        </div>
                        <div className="truncate text-xs text-muted-foreground">
                            ID: {row.original.account_id || '-'}
                        </div>
                        {row.original.disable_reason && (
                            <div
                                className={
                                    row.original.disable_reason_severity ===
                                    'error'
                                        ? 'mt-1 text-xs font-medium text-red-600'
                                        : 'mt-1 text-xs font-medium text-orange-600'
                                }
                            >
                                {row.original.disable_reason}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                accessorKey: 'customer_name',
                header: t('service_management.customer', {
                    defaultValue: 'Khách hàng',
                }),
                cell: ({ row }) => (
                    <div className="min-w-0 truncate">
                        {row.original.customer_name ||
                            row.original.owner_name ||
                            '-'}
                    </div>
                ),
            },
            {
                accessorKey: 'bm_name',
                header: t('service_management.bm_mcc', {
                    defaultValue: 'BM / MCC',
                }),
                cell: ({ row }) => {
                    const bmId = row.original.bm_ids?.[0] ?? '-';
                    return (
                        <div className="min-w-0">
                            <div className="truncate">
                                {row.original.bm_name || bmId}
                            </div>
                            <div className="truncate text-xs text-muted-foreground">
                                ID: {bmId}
                            </div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'total_spend',
                header: t('service_management.amount_spent', {
                    defaultValue: 'Amount spent',
                }),
                cell: ({ row }) =>
                    formatAccountCurrency(
                        row.original.total_spend,
                        row.original.currency,
                    ),
                meta: {
                    cellClassName: 'whitespace-nowrap text-right font-medium',
                },
            },
            {
                accessorKey: 'account_status_label',
                header: t('service_management.account_status', {
                    defaultValue: 'Account status',
                }),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.account_status_label
                                ? 'secondary'
                                : 'outline'
                        }
                        className={getAccountStatusClassName(
                            row.original.account_status_severity,
                            row.original.account_status_label,
                        )}
                        title={row.original.disable_reason || undefined}
                    >
                        {row.original.account_status_label ||
                            row.original.account_status ||
                            '-'}
                    </Badge>
                ),
                meta: { cellClassName: 'whitespace-nowrap' },
            },
            {
                accessorKey: 'spend_cap',
                header: t('service_management.account_spending_limit', {
                    defaultValue: 'Account spending limit',
                }),
                cell: ({ row }) =>
                    formatAccountCurrency(
                        row.original.spend_cap,
                        row.original.currency,
                    ),
                meta: { cellClassName: 'whitespace-nowrap text-right' },
            },
            {
                accessorKey: 'remaining_amount',
                header: t('service_management.remaining_amount', {
                    defaultValue: 'Remaining amount',
                }),
                cell: ({ row }) =>
                    formatAccountCurrency(
                        row.original.remaining_amount,
                        row.original.currency,
                    ),
                meta: { cellClassName: 'whitespace-nowrap text-right' },
            },
            {
                accessorKey: 'amount_spent',
                header: t('service_management.total_spending', {
                    defaultValue: 'Total spending',
                }),
                cell: ({ row }) =>
                    formatAccountCurrency(
                        row.original.amount_spent,
                        row.original.currency,
                    ),
                meta: { cellClassName: 'whitespace-nowrap text-right' },
            },
            {
                accessorKey: 'total_balance',
                header: t('service_management.unpaid_balance', {
                    defaultValue: 'Nợ chưa thanh toán',
                }),
                cell: ({ row }) =>
                    formatAccountCurrency(
                        row.original.total_balance,
                        row.original.currency,
                    ),
                meta: { cellClassName: 'whitespace-nowrap text-right' },
            },
            {
                accessorKey: 'created_time',
                header: t('service_management.creation_time', {
                    defaultValue: 'Creation time',
                }),
                cell: ({ row }) => formatDateTime(row.original.created_time),
                meta: { cellClassName: 'whitespace-nowrap' },
            },
            {
                accessorKey: 'timezone',
                header: t('service_management.timezone', {
                    defaultValue: 'Timezone',
                }),
                cell: ({ row }) => row.original.timezone || '-',
                meta: { cellClassName: 'whitespace-nowrap' },
            },
            {
                accessorKey: 'payment_card',
                header: t('service_management.payment_card', {
                    defaultValue: 'Payment card',
                }),
                cell: ({ row }) => row.original.payment_card || '-',
                meta: { cellClassName: 'whitespace-nowrap' },
            },
            {
                id: 'actions',
                header: t('common.actions', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    const account = row.original;
                    const canViewCampaigns =
                        !!account.service_user_id ||
                        account.platform === _PlatformType.META;
                    return (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => loadCampaigns(account)}
                            disabled={!canViewCampaigns}
                            title={
                                !canViewCampaigns
                                    ? t(
                                          'service_management.account_not_assigned',
                                          {
                                              defaultValue:
                                                  'Tài khoản này chưa được gán với user nào',
                                          },
                                      )
                                    : t(
                                          'service_management.view_campaigns_tooltip',
                                          {
                                              defaultValue:
                                                  'Xem danh sách chiến dịch đã được sync từ API',
                                          },
                                      )
                            }
                        >
                            {t('service_management.view_campaigns', {
                                defaultValue: 'Xem chiến dịch',
                            })}
                        </Button>
                    );
                },
            },
        ],
        [t, loadCampaigns],
    );

    const campaignColumns: ColumnDef<Campaign>[] = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: t('service_management.campaign_name', {
                    defaultValue: 'Chiến dịch',
                }),
                cell: ({ row }) => {
                    const rawName =
                        row.original.name || row.original.campaign_id || '-';

                    return (
                        <div className="min-w-0">
                            <div className="truncate font-medium">
                                {rawName || '-'}
                            </div>
                            <div className="truncate text-xs text-muted-foreground">
                                ID: {row.original.campaign_id}
                            </div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'effective_status',
                header: t('service_management.status', {
                    defaultValue: 'Trạng thái',
                }),
                cell: ({ row }) => (
                    <Badge
                        variant={getSeverityBadge(row.original.status_severity)}
                    >
                        {row.original.status_label ||
                            row.original.effective_status ||
                            row.original.status ||
                            '-'}
                    </Badge>
                ),
            },
            {
                accessorKey: 'daily_budget',
                header: t('service_management.daily_budget', {
                    defaultValue: 'Ngân sách/ngày',
                }),
                cell: ({ row }) => formatCurrency(row.original.daily_budget),
            },
            {
                accessorKey: 'today_spend',
                header: t('service_management.today_spend', {
                    defaultValue: 'Chi tiêu hôm nay',
                }),
                cell: ({ row }) => formatCurrency(row.original.today_spend),
            },
            {
                accessorKey: 'total_spend',
                header:
                    campaignDateRange?.from && campaignDateRange?.to
                        ? t('service_management.period_spend', {
                              defaultValue: 'Chi tiêu theo khoảng ngày',
                          })
                        : t('service_management.total_spend', {
                              defaultValue: 'Tổng chi tiêu',
                          }),
                cell: ({ row }) => formatCurrency(row.original.total_spend),
            },
            {
                id: 'actions',
                header: t('common.actions', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    const campaign = row.original;
                    const canViewDetail = !!selectedAccount?.service_user_id;
                    return (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => loadCampaignDetail(campaign)}
                            disabled={!canViewDetail}
                        >
                            {t('common.view_details', {
                                defaultValue: 'Xem chi tiết',
                            })}
                        </Button>
                    );
                },
            },
        ],
        [
            campaignDateRange,
            t,
            loadCampaignDetail,
            selectedAccount?.service_user_id,
        ],
    );

    const insightPresetOptions = [
        {
            label: t('service_management.spend_chart_preset_7d'),
            value: 'last_7d',
        },
        {
            label: t('service_management.spend_chart_preset_30d'),
            value: 'last_30d',
        },
    ];

    const renderSpendChart = () => {
        if (!selectedCampaign) return null;
        const spendChangeNumber = parseNumber(
            campaignDetail?.insight?.spend?.percent_change,
        );
        const spendChangeText = formatPercentChange(
            campaignDetail?.insight?.spend?.percent_change,
        );
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
                        <CardTitle>
                            {t('service_management.spend_chart_title')}
                        </CardTitle>
                        <CardDescription className={spendChangeColor}>
                            {spendChangeNumber === null
                                ? t('service_management.spend_chart_no_change')
                                : t(
                                      'service_management.spend_chart_description',
                                      { percent: spendChangeText },
                                  )}
                        </CardDescription>
                    </div>
                    <Select
                        value={insightPreset}
                        onValueChange={(value) =>
                            handleInsightPresetChange(
                                value as 'last_7d' | 'last_30d',
                            )
                        }
                        disabled={campaignInsightsLoading}
                    >
                        <SelectTrigger className="w-[140px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {insightPresetOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent>
                    {campaignInsightsError && (
                        <Alert variant="destructive" className="mb-4">
                            <AlertTitle>
                                {t('service_management.campaign_insight_error')}
                            </AlertTitle>
                            <AlertDescription>
                                {campaignInsightsError}
                            </AlertDescription>
                        </Alert>
                    )}

                    {campaignInsightsLoading ? (
                        <div className="flex items-center justify-center py-10 text-muted-foreground">
                            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                            {t('service_management.loading')}
                        </div>
                    ) : chartEntries.length === 0 ? (
                        <div className="py-6 text-sm text-muted-foreground">
                            {t('service_management.spend_chart_empty')}
                        </div>
                    ) : (
                        <ChartContainer height={256} className="w-full">
                            <BarChart
                                data={chartEntries}
                                margin={{
                                    top: 10,
                                    right: 10,
                                    left: 0,
                                    bottom: 20,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={false}
                                />
                                <XAxis
                                    dataKey="label"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    style={{ fontSize: '11px' }}
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    tickFormatter={(val) => `$${val}`}
                                    style={{ fontSize: '11px' }}
                                />
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent
                                            formatter={(value: any) =>
                                                typeof value === 'number'
                                                    ? formatCurrency(value)
                                                    : (value ?? '--')
                                            }
                                        />
                                    }
                                />
                                <Bar
                                    dataKey="value"
                                    name={t(
                                        'service_management.spend_chart_series',
                                    )}
                                    fill="hsl(var(--primary))"
                                    radius={[4, 4, 0, 0]}
                                >
                                    {chartEntries.map((entry, index) => (
                                        <Cell
                                            key={`cell-${index}`}
                                            fill="hsl(var(--primary))"
                                            fillOpacity={0.8}
                                        />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ChartContainer>
                    )}
                </CardContent>
            </Card>
        );
    };

    const renderCampaignView = () => {
        return (
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            {t('service_management.campaigns', {
                                defaultValue: 'Chiến dịch',
                            })}
                        </h2>
                        <p className="text-muted-foreground">
                            {selectedAccount?.account_name ||
                                selectedAccount?.account_id}{' '}
                            •{' '}
                            {selectedAccount?.platform === _PlatformType.META
                                ? 'Meta'
                                : 'Google'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {selectedCampaign && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setSelectedCampaign(null);
                                    setCampaignDetail(null);
                                }}
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('service_management.back_to_campaigns', {
                                    defaultValue: 'Quay lại danh sách',
                                })}
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() => {
                                setSelectedAccount(null);
                                setCampaigns([]);
                                setCampaignError(null);
                                setSelectedCampaign(null);
                                setCampaignDetail(null);
                            }}
                        >
                            {!selectedCampaign && (
                                <ArrowLeft className="mr-2 h-4 w-4" />
                            )}
                            {t('service_management.back_to_accounts', {
                                defaultValue: 'Quay lại tài khoản',
                            })}
                        </Button>
                    </div>
                </div>

                {campaignError && (
                    <Alert variant="destructive">
                        <AlertTitle>
                            {t('common_error.server_error', {
                                defaultValue: 'Có lỗi xảy ra',
                            })}
                        </AlertTitle>
                        <AlertDescription>{campaignError}</AlertDescription>
                    </Alert>
                )}

                {campaignLoading ? (
                    <div className="flex items-center justify-center rounded-md border bg-white p-8 text-muted-foreground">
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        {t('common.loading', { defaultValue: 'Đang tải...' })}
                    </div>
                ) : !selectedCampaign ? (
                    <>
                        {issueCampaigns.length > 0 && (
                            <Alert variant="destructive">
                                <AlertTitle>
                                    {t(
                                        'service_management.campaign_issue_title',
                                        { defaultValue: 'Cảnh báo chiến dịch' },
                                    )}
                                </AlertTitle>
                                <AlertDescription>
                                    {t(
                                        'service_management.campaign_issue_description',
                                        {
                                            defaultValue:
                                                '{{error}} chiến dịch đã bị nền tảng dừng. Kiểm tra ngay.',
                                            error: issueCampaigns.length,
                                        },
                                    )}
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="flex flex-wrap items-end gap-3 rounded-md border bg-white p-3">
                            <div className="grid gap-2">
                                <Label>
                                    {t(
                                        'service_management.campaign_spend_period',
                                        {
                                            defaultValue:
                                                'Khoảng thời gian chi tiêu',
                                        },
                                    )}
                                </Label>
                                <DateRangePicker
                                    date={campaignDateRange}
                                    onDateChange={setCampaignDateRange}
                                />
                            </div>
                            <Button
                                variant="outline"
                                onClick={() => loadCampaigns()}
                                disabled={campaignLoading}
                            >
                                <RefreshCw className="mr-2 h-4 w-4" />
                                {t('common.filter', { defaultValue: 'Lọc' })}
                            </Button>
                            <Button
                                variant="ghost"
                                onClick={() => {
                                    setCampaignDateRange(undefined);
                                    loadCampaigns(undefined, null);
                                }}
                                disabled={campaignLoading}
                            >
                                {t('common.reset', { defaultValue: 'Đặt lại' })}
                            </Button>
                        </div>

                        <DataTable
                            columns={campaignColumns}
                            paginator={{
                                data: campaigns,
                                links: {
                                    first: null,
                                    last: null,
                                    prev: null,
                                    next: null,
                                },
                                meta: {
                                    links: [],
                                    current_page: 1,
                                    from: campaigns.length ? 1 : 0,
                                    last_page: 1,
                                    per_page: campaigns.length || 1,
                                    to: campaigns.length ? campaigns.length : 0,
                                    total: campaigns.length,
                                },
                            }}
                        />
                    </>
                ) : (
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between">
                                    <span>
                                        {t(
                                            'service_management.campaign_detail',
                                        )}
                                    </span>
                                    {campaignDetail && (
                                        <Badge
                                            variant={getSeverityBadge(
                                                campaignDetail.status_severity,
                                            )}
                                        >
                                            {campaignDetail.status_label ||
                                                campaignDetail.effective_status ||
                                                campaignDetail.status}
                                        </Badge>
                                    )}
                                </CardTitle>
                                <CardDescription>
                                    {selectedCampaign.name ||
                                        selectedCampaign.campaign_id}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {campaignDetailLoading ? (
                                    <div className="flex items-center justify-center py-10">
                                        <Loader2 className="mr-2 h-6 w-6 animate-spin" />
                                        {t('service_management.loading')}
                                    </div>
                                ) : campaignDetailError ? (
                                    <Alert variant="destructive">
                                        <AlertTitle>
                                            {t(
                                                'service_management.campaign_detail_error',
                                            )}
                                        </AlertTitle>
                                        <AlertDescription>
                                            {campaignDetailError}
                                        </AlertDescription>
                                    </Alert>
                                ) : campaignDetail ? (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <div className="text-sm text-muted-foreground">
                                                    {t(
                                                        'service_management.today_spend',
                                                    )}
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    {formatCurrency(
                                                        campaignDetail.today_spend,
                                                    )}
                                                </div>
                                            </div>
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <div className="text-sm text-muted-foreground">
                                                    {t(
                                                        'service_management.total_spend',
                                                    )}
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    {formatCurrency(
                                                        campaignDetail.total_spend,
                                                    )}
                                                </div>
                                            </div>
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <div className="text-sm text-muted-foreground">
                                                    CPC
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    {formatCurrency(
                                                        campaignDetail.cpc_avg,
                                                    )}
                                                </div>
                                            </div>
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <div className="text-sm text-muted-foreground">
                                                    ROAS
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    {(() => {
                                                        const value =
                                                            formatNumber(
                                                                campaignDetail.roas_avg,
                                                                {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 2,
                                                                },
                                                            );
                                                        return value === '--'
                                                            ? value
                                                            : `${value}x`;
                                                    })()}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {campaignDetail.insight &&
                                                Object.entries(
                                                    campaignDetail.insight,
                                                ).map(([key, value]) => {
                                                    if (
                                                        [
                                                            'today_spend',
                                                            'total_spend',
                                                            'cpc_avg',
                                                            'roas_avg',
                                                        ].includes(key)
                                                    )
                                                        return null;
                                                    const val = value as any;
                                                    const percentNumber =
                                                        parseNumber(
                                                            val?.percent_change,
                                                        );
                                                    const percentText =
                                                        formatPercentChange(
                                                            val?.percent_change,
                                                        );
                                                    const PercentIcon =
                                                        percentNumber === null
                                                            ? null
                                                            : percentNumber >= 0
                                                              ? TrendingUp
                                                              : TrendingDown;
                                                    const percentColor =
                                                        percentNumber === null
                                                            ? 'text-muted-foreground'
                                                            : percentNumber >= 0
                                                              ? 'text-green-500'
                                                              : 'text-red-500';

                                                    return (
                                                        <Card key={key}>
                                                            <CardHeader className="px-4 pb-3">
                                                                <CardTitle className="text-sm font-medium capitalize">
                                                                    {key.replace(
                                                                        '_',
                                                                        ' ',
                                                                    )}
                                                                </CardTitle>
                                                            </CardHeader>
                                                            <CardContent className="space-y-2 px-4 pb-4">
                                                                <div className="flex items-center justify-between">
                                                                    <span className="text-xs text-muted-foreground">
                                                                        {t(
                                                                            'service_management.today',
                                                                        )}
                                                                    </span>
                                                                    <span className="text-sm font-semibold">
                                                                        {formatNumber(
                                                                            val?.today,
                                                                        )}
                                                                    </span>
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <span className="text-xs text-muted-foreground">
                                                                        {t(
                                                                            'service_management.total',
                                                                        )}
                                                                    </span>
                                                                    <span className="text-sm font-semibold">
                                                                        {formatNumber(
                                                                            val?.total,
                                                                        )}
                                                                    </span>
                                                                </div>
                                                                <div className="flex items-center justify-between border-t pt-2">
                                                                    <span className="text-xs text-muted-foreground">
                                                                        {t(
                                                                            'service_management.change',
                                                                        )}
                                                                    </span>
                                                                    <div className="flex items-center gap-1">
                                                                        {PercentIcon && (
                                                                            <PercentIcon
                                                                                className={`h-3 w-3 ${percentColor}`}
                                                                            />
                                                                        )}
                                                                        <span
                                                                            className={`text-xs font-semibold ${percentColor}`}
                                                                        >
                                                                            {
                                                                                percentText
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </CardContent>
                                                        </Card>
                                                    );
                                                })}
                                        </div>

                                        <div className="mt-4 flex flex-wrap gap-3">
                                            {(() => {
                                                const rawStatus =
                                                    campaignDetail.effective_status ||
                                                    campaignDetail.status;
                                                const normalizedStatus =
                                                    rawStatus
                                                        ? String(
                                                              rawStatus,
                                                          ).toUpperCase()
                                                        : '';
                                                const isDeleted = [
                                                    'DELETED',
                                                    'ARCHIVED',
                                                    'REMOVED',
                                                ].includes(normalizedStatus);
                                                const isPaused =
                                                    normalizedStatus ===
                                                    'PAUSED';

                                                return (
                                                    <>
                                                        {isPaused ? (
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setResumeDialogOpen(
                                                                        true,
                                                                    )
                                                                }
                                                                disabled={
                                                                    isDeleted
                                                                }
                                                            >
                                                                <Play className="mr-2 h-4 w-4" />
                                                                {t(
                                                                    'service_management.campaign_resume',
                                                                )}
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setPauseDialogOpen(
                                                                        true,
                                                                    )
                                                                }
                                                                disabled={
                                                                    isDeleted
                                                                }
                                                            >
                                                                <Pause className="mr-2 h-4 w-4" />
                                                                {t(
                                                                    'service_management.campaign_pause',
                                                                )}
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="destructive"
                                                            onClick={() =>
                                                                setEndDialogOpen(
                                                                    true,
                                                                )
                                                            }
                                                            disabled={isDeleted}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            {t(
                                                                'service_management.campaign_end',
                                                            )}
                                                        </Button>
                                                        <Button
                                                            variant="default"
                                                            onClick={async () => {
                                                                setBudgetDialogOpen(
                                                                    true,
                                                                );
                                                                if (
                                                                    isAgencyOrCustomer &&
                                                                    walletBalance ===
                                                                        null &&
                                                                    !walletBalanceLoading
                                                                ) {
                                                                    setWalletBalanceLoading(
                                                                        true,
                                                                    );
                                                                    try {
                                                                        const response =
                                                                            await axios.get(
                                                                                '/wallet/me',
                                                                            );
                                                                        setWalletBalance(
                                                                            response
                                                                                .data
                                                                                ?.data
                                                                                ?.balance ??
                                                                                0,
                                                                        );
                                                                    } catch (e) {
                                                                        console.error(
                                                                            'Failed to fetch wallet balance',
                                                                            e,
                                                                        );
                                                                    } finally {
                                                                        setWalletBalanceLoading(
                                                                            false,
                                                                        );
                                                                    }
                                                                }
                                                            }}
                                                            disabled={isDeleted}
                                                        >
                                                            <Wallet className="mr-2 h-4 w-4" />
                                                            {t(
                                                                'service_management.campaign_update_budget',
                                                            )}
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
                    </div>
                )}
            </div>
        );
    };

    const issueCampaigns = useMemo(
        () =>
            campaigns.filter(
                (c) => c.status_severity && c.status_severity !== 'success',
            ),
        [campaigns],
    );

    return (
        <>
            <Head
                title={t('menu.service_management', {
                    defaultValue: 'Quản lý tài khoản',
                })}
            />
            <div className="space-y-6">
                {!selectedAccount ? (
                    <>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {t('service_management.title', {
                                    defaultValue: 'Quản lý tài khoản',
                                })}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('service_management.subtitle', {
                                    defaultValue:
                                        'Danh sách tài khoản quảng cáo',
                                })}
                            </p>
                        </div>

                        {/* Tổng quan tài khoản (tổng/active/disabled) */}
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.total', {
                                            defaultValue:
                                                'Tổng số lượng tài khoản',
                                        })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold">
                                    {Number(stats.total_accounts ?? 0)}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.active', {
                                            defaultValue: 'Active',
                                        })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold text-green-600">
                                    {Number(stats.active_accounts ?? 0)}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.disabled', {
                                            defaultValue: 'Disabled',
                                        })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold text-red-600">
                                    {Number(stats.disabled_accounts ?? 0)}
                                </CardContent>
                            </Card>
                        </div>

                        <BusinessManagerSearchForm
                            query={query}
                            setQuery={setQuery}
                            handleSearch={handleSearch}
                            handleReset={handleReset}
                        />
                        {query.platform === _PlatformType.META && (
                            <div className="-mt-4 flex flex-col gap-2 text-sm text-muted-foreground md:flex-row md:items-center md:justify-end">
                                <span>
                                    {t('service_management.last_synced_at', {
                                        defaultValue: 'Cập nhật lần cuối',
                                    })}
                                    : {formatDateTimeFull(lastSyncedAt)}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleSyncMetaInsights}
                                    disabled={
                                        syncMetaSubmitting || !selectedMetaBmId
                                    }
                                >
                                    {syncMetaSubmitting ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <RefreshCw />
                                    )}
                                    {t('service_management.sync_meta', {
                                        defaultValue: 'Cập nhật dữ liệu Meta',
                                    })}
                                </Button>
                            </div>
                        )}

                        <DataTable
                            columns={accountColumns}
                            paginator={paginator}
                            renderFooterRows={(columnCount) => (
                                <TableRow className="bg-muted/40 font-medium">
                                    <TableCell colSpan={3}>
                                        <div>
                                            {t(
                                                'service_management.total_results',
                                                {
                                                    defaultValue:
                                                        'Total results',
                                                },
                                            )}
                                        </div>
                                        <div className="text-xs font-normal text-muted-foreground">
                                            {t(
                                                'service_management.rows_displayed',
                                                {
                                                    defaultValue:
                                                        '{{shown}} / {{total}} rows displayed',
                                                    shown: paginator.data
                                                        .length,
                                                    total: paginator.meta.total,
                                                },
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right whitespace-nowrap">
                                        {formatTotalsSpend()}
                                    </TableCell>
                                    {columnCount > 4 ? (
                                        <TableCell colSpan={columnCount - 4} />
                                    ) : null}
                                </TableRow>
                            )}
                        />
                    </>
                ) : (
                    renderCampaignView()
                )}
            </div>

            {/* Dialog Cập nhật ngân sách */}
            <Dialog open={budgetDialogOpen} onOpenChange={setBudgetDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.campaign_update_budget')}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedAccount?.platform === _PlatformType.META
                                ? t(
                                      'service_management.campaign_update_budget_help_meta',
                                  )
                                : t(
                                      'service_management.campaign_update_budget_help_google',
                                  )}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        {isAgencyOrCustomer && (
                            <div className="text-sm text-muted-foreground">
                                {walletBalanceLoading
                                    ? t(
                                          'service_management.campaign_update_budget_wallet_balance_loading',
                                      )
                                    : walletBalance !== null
                                      ? t(
                                            'service_management.campaign_update_budget_wallet_balance',
                                            {
                                                balance:
                                                    walletBalance.toLocaleString(),
                                            },
                                        )
                                      : null}
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="budget">
                                {t(
                                    'service_management.campaign_update_budget_amount_label',
                                )}
                            </Label>
                            <Input
                                id="budget"
                                type="number"
                                value={budgetAmount}
                                onChange={(e) =>
                                    setBudgetAmount(e.target.value)
                                }
                                placeholder="0.00"
                            />
                        </div>
                        {isAgencyOrCustomer && (
                            <div className="space-y-2">
                                <Label htmlFor="password">
                                    {t(
                                        'service_management.campaign_update_budget_wallet_password_label',
                                    )}
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={budgetWalletPassword}
                                    onChange={(e) =>
                                        setBudgetWalletPassword(e.target.value)
                                    }
                                />
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setBudgetDialogOpen(false)}
                            disabled={budgetSubmitting}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            onClick={async () => {
                                if (
                                    !budgetAmount ||
                                    Number(budgetAmount) <= 0
                                ) {
                                    toast.error(t('common.invalid_amount'));
                                    return;
                                }
                                setBudgetSubmitting(true);
                                try {
                                    const platformPrefix =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'google-ads'
                                            : 'meta';
                                    const fieldName =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'budget'
                                            : 'spend-cap';
                                    await axios.post(
                                        `/${platformPrefix}/${selectedAccount?.service_user_id}/${selectedCampaign?.id}/${fieldName}`,
                                        {
                                            amount: Number(budgetAmount),
                                            wallet_password:
                                                budgetWalletPassword,
                                        },
                                    );
                                    toast.success(
                                        t(
                                            'service_management.campaign_update_budget_success',
                                            { amount: budgetAmount },
                                        ),
                                    );
                                    setBudgetDialogOpen(false);
                                    setBudgetAmount('');
                                    setBudgetWalletPassword('');
                                    refreshCurrentCampaign();
                                } catch (e: any) {
                                    toast.error(
                                        e?.response?.data?.message ||
                                            t(
                                                'service_management.campaign_update_budget_error',
                                            ),
                                    );
                                } finally {
                                    setBudgetSubmitting(false);
                                }
                            }}
                            disabled={budgetSubmitting}
                        >
                            {budgetSubmitting && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {t('common.submit')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Tạm dừng */}
            <Dialog open={pauseDialogOpen} onOpenChange={setPauseDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.campaign_pause')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('service_management.campaign_pause_warning')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setPauseDialogOpen(false)}
                            disabled={pauseSubmitting}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={async () => {
                                setPauseSubmitting(true);
                                try {
                                    const platformPrefix =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'google-ads'
                                            : 'meta';
                                    await axios.post(
                                        `/${platformPrefix}/${selectedAccount?.service_user_id}/${selectedCampaign?.id}/status`,
                                        {
                                            status: 'PAUSED',
                                        },
                                    );
                                    toast.success(
                                        t(
                                            'service_management.campaign_pause_success',
                                        ),
                                    );
                                    setPauseDialogOpen(false);
                                    refreshCurrentCampaign();
                                } catch (e: any) {
                                    toast.error(
                                        e?.response?.data?.message ||
                                            t(
                                                'service_management.campaign_pause_error',
                                            ),
                                    );
                                } finally {
                                    setPauseSubmitting(false);
                                }
                            }}
                            disabled={pauseSubmitting}
                        >
                            {pauseSubmitting && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {t('common.confirm')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Tiếp tục */}
            <Dialog open={resumeDialogOpen} onOpenChange={setResumeDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.campaign_resume')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('service_management.campaign_resume_warning')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setResumeDialogOpen(false)}
                            disabled={resumeSubmitting}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            onClick={async () => {
                                setResumeSubmitting(true);
                                try {
                                    const platformPrefix =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'google-ads'
                                            : 'meta';
                                    const status =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'ENABLED'
                                            : 'ACTIVE';
                                    await axios.post(
                                        `/${platformPrefix}/${selectedAccount?.service_user_id}/${selectedCampaign?.id}/status`,
                                        {
                                            status,
                                        },
                                    );
                                    toast.success(
                                        t(
                                            'service_management.campaign_resume_success',
                                        ),
                                    );
                                    setResumeDialogOpen(false);
                                    refreshCurrentCampaign();
                                } catch (e: any) {
                                    toast.error(
                                        e?.response?.data?.message ||
                                            t(
                                                'service_management.campaign_resume_error',
                                            ),
                                    );
                                } finally {
                                    setResumeSubmitting(false);
                                }
                            }}
                            disabled={resumeSubmitting}
                        >
                            {resumeSubmitting && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {t('common.confirm')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Kết thúc */}
            <Dialog open={endDialogOpen} onOpenChange={setEndDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.campaign_end')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('service_management.campaign_end_warning')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEndDialogOpen(false)}
                            disabled={endSubmitting}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={async () => {
                                setEndSubmitting(true);
                                try {
                                    const platformPrefix =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'google-ads'
                                            : 'meta';
                                    const status =
                                        selectedAccount?.platform ===
                                        _PlatformType.GOOGLE
                                            ? 'REMOVED'
                                            : 'DELETED';
                                    await axios.post(
                                        `/${platformPrefix}/${selectedAccount?.service_user_id}/${selectedCampaign?.id}/status`,
                                        {
                                            status,
                                        },
                                    );
                                    toast.success(
                                        t(
                                            'service_management.campaign_end_success',
                                        ),
                                    );
                                    setEndDialogOpen(false);
                                    setSelectedCampaign(null);
                                    setCampaignDetail(null);
                                    refreshCampaignListOnly();
                                } catch (e: any) {
                                    toast.error(
                                        e?.response?.data?.message ||
                                            t(
                                                'service_management.campaign_end_error',
                                            ),
                                    );
                                } finally {
                                    setEndSubmitting(false);
                                }
                            }}
                            disabled={endSubmitting}
                        >
                            {endSubmitting && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {t('common.confirm')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
};

ServiceManagementIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.service_management' }]}
        children={page}
    />
);

export default ServiceManagementIndex;
