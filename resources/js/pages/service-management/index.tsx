import { useMemo, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import type { ColumnDef } from '@tanstack/react-table';
import axios from 'axios';
import { Search, ArrowLeft, Loader2 } from 'lucide-react';

import { DataTable } from '@/components/table/data-table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

import { _PlatformType } from '@/lib/types/constants';
import { service_management_index } from '@/routes';
import type { BusinessManagerItem, BusinessManagerPagination, BusinessManagerStats } from '@/pages/business-manager/types/type';
import type { Campaign, StatusSeverity } from '@/pages/service-management/types/types';

type Props = {
    paginator: BusinessManagerPagination;
    stats: BusinessManagerStats;
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

const ServiceManagementIndex = ({ paginator, stats }: Props) => {
    const { t } = useTranslation();
    const keywordInputRef = useRef<HTMLInputElement | null>(null);

    const accounts = paginator?.data ?? [];
    const [selectedAccount, setSelectedAccount] = useState<BusinessManagerItem | null>(null);
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [campaignLoading, setCampaignLoading] = useState(false);
    const [campaignError, setCampaignError] = useState<string | null>(null);

    const urlKeyword =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('filter[keyword]')
            : null;

    const onSearch = () => {
        const value = keywordInputRef.current?.value || '';
        window.location.href = service_management_index({ query: { filter: { keyword: value } } }).url;
    };

    const loadCampaigns = async (account: BusinessManagerItem) => {
        if (!account?.service_user_id) {
            setCampaignError(
                t('service_management.account_not_assigned', { defaultValue: 'Tài khoản này chưa được gán với user nào' }),
            );
            return;
        }

        setSelectedAccount(account);
        setCampaignError(null);
        setCampaignLoading(true);
        setCampaigns([]);

        try {
            const apiPath =
                account.platform === _PlatformType.GOOGLE
                    ? `/google-ads/${account.service_user_id}/${account.id}/campaigns`
                    : `/meta/${account.service_user_id}/${account.id}/campaigns`;

            const response = await axios.get(apiPath, { params: { per_page: 50 } });
            const payload = response.data?.data;
            const items: any[] = Array.isArray(payload?.data)
                ? payload.data
                : Array.isArray(payload)
                    ? payload
                    : [];
            setCampaigns(items as Campaign[]);
        } catch (e: any) {
            setCampaignError(e?.response?.data?.message || t('service_management.campaigns_error', { defaultValue: 'Không thể tải chiến dịch' }));
        } finally {
            setCampaignLoading(false);
        }
    };

    const accountColumns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('service_management.account_name', { defaultValue: 'Tài khoản quảng cáo' }),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <div className="font-medium truncate">{row.original.account_name || '-'}</div>
                        <div className="text-xs text-muted-foreground truncate">ID: {row.original.account_id || '-'}</div>
                    </div>
                ),
            },
            {
                accessorKey: 'bm_name',
                header: t('service_management.bm_mcc', { defaultValue: 'BM / MCC' }),
                cell: ({ row }) => {
                    const bmId = row.original.bm_ids?.[0] ?? '-';
                    return (
                        <div className="min-w-0">
                            <div className="truncate">{row.original.bm_name || bmId}</div>
                            <div className="text-xs text-muted-foreground truncate">ID: {bmId}</div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'platform',
                header: t('service_management.platform', { defaultValue: 'Nền tảng' }),
                cell: ({ row }) => (
                    <Badge variant="secondary">
                        {row.original.platform === _PlatformType.META ? 'Meta' : 'Google'}
                    </Badge>
                ),
            },
            {
                accessorKey: 'owner_name',
                header: t('service_management.owner', { defaultValue: 'Chủ' }),
                cell: ({ row }) => row.original.owner_name || '-',
            },
            {
                accessorKey: 'total_spend',
                header: t('service_management.spend', { defaultValue: 'Chi tiêu' }),
                cell: ({ row }) => {
                    const spend = Number(row.original.total_spend || 0);
                    return `${spend.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${row.original.currency || 'USD'}`;
                },
            },
            {
                accessorKey: 'total_balance',
                header: t('service_management.balance', { defaultValue: 'Số dư' }),
                cell: ({ row }) => {
                    const bal = Number(row.original.total_balance || 0);
                    return `${bal.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${row.original.currency || 'USD'}`;
                },
            },
            {
                id: 'actions',
                header: t('common.actions', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    const account = row.original;
                    const hasServiceUser = !!account.service_user_id;
                    return (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => loadCampaigns(account)}
                            disabled={!hasServiceUser}
                            title={
                                !hasServiceUser
                                    ? t('service_management.account_not_assigned', {
                                          defaultValue: 'Tài khoản này chưa được gán với user nào',
                                      })
                                    : t('service_management.view_campaigns_tooltip', {
                                          defaultValue: 'Xem danh sách chiến dịch đã được sync từ API',
                                      })
                            }
                        >
                            {t('service_management.view_campaigns', { defaultValue: 'Xem chiến dịch' })}
                        </Button>
                    );
                },
            },
        ],
        [t],
    );

    const campaignColumns: ColumnDef<Campaign>[] = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: t('service_management.campaign_name', { defaultValue: 'Chiến dịch' }),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <div className="font-medium truncate">{row.original.name || row.original.campaign_id}</div>
                        <div className="text-xs text-muted-foreground truncate">ID: {row.original.campaign_id}</div>
                    </div>
                ),
            },
            {
                accessorKey: 'effective_status',
                header: t('service_management.status', { defaultValue: 'Trạng thái' }),
                cell: ({ row }) => (
                    <Badge variant={getSeverityBadge(row.original.status_severity)}>
                        {row.original.status_label || row.original.effective_status || row.original.status || '-'}
                    </Badge>
                ),
            },
            {
                accessorKey: 'daily_budget',
                header: t('service_management.daily_budget', { defaultValue: 'Ngân sách/ngày' }),
                cell: ({ row }) => row.original.daily_budget || '-',
            },
            {
                accessorKey: 'today_spend',
                header: t('service_management.today_spend', { defaultValue: 'Chi tiêu hôm nay' }),
                cell: ({ row }) => row.original.today_spend || '-',
            },
            {
                accessorKey: 'total_spend',
                header: t('service_management.total_spend', { defaultValue: 'Tổng chi tiêu' }),
                cell: ({ row }) => row.original.total_spend || '-',
            },
        ],
        [t],
    );

    const issueCampaigns = useMemo(
        () => campaigns.filter((c) => c.status_severity && c.status_severity !== 'success'),
        [campaigns],
    );

    return (
        <>
            <Head title={t('menu.service_management', { defaultValue: 'Quản lý tài khoản' })} />
            <div className="space-y-6">
                {!selectedAccount ? (
                    <>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {t('service_management.title', { defaultValue: 'Quản lý tài khoản' })}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('service_management.subtitle', { defaultValue: 'Danh sách tài khoản quảng cáo' })}
                            </p>
                        </div>

                        {/* Tổng quan tài khoản (tổng/active/disabled) */}
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.total', { defaultValue: 'Tổng số lượng tài khoản' })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold">
                                    {Number(stats.total_accounts ?? 0)}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.active', { defaultValue: 'Active' })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold text-green-600">
                                    {Number(stats.active_accounts ?? 0)}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">
                                        {t('business_manager.stats.disabled', { defaultValue: 'Disabled' })}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold text-red-600">
                                    {Number(stats.disabled_accounts ?? 0)}
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('common.search', { defaultValue: 'Tìm kiếm' })}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                    <div className="space-y-1">
                                        <label className="text-sm font-medium">
                                            {t('common.keyword', { defaultValue: 'Từ khóa' })}
                                        </label>
                                        <Input
                                            ref={keywordInputRef}
                                            autoComplete="off"
                                            defaultValue={urlKeyword || ''}
                                            placeholder={t('common.keyword', { defaultValue: 'Từ khóa' })}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    onSearch();
                                                }
                                            }}
                                        />
                                    </div>
                                </div>
                                <div>
                                    <Button type="button" onClick={onSearch}>
                                        <Search className="mr-2 h-4 w-4" />
                                        {t('common.search', { defaultValue: 'Tìm kiếm' })}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <DataTable columns={accountColumns} paginator={paginator} />
                    </>
                ) : (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold">
                                    {t('service_management.campaigns', { defaultValue: 'Chiến dịch' })}
                                </h2>
                                <p className="text-muted-foreground">
                                    {selectedAccount.account_name || selectedAccount.account_id} •{' '}
                                    {selectedAccount.platform === _PlatformType.META ? 'Meta' : 'Google'}
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setSelectedAccount(null);
                                    setCampaigns([]);
                                    setCampaignError(null);
                                }}
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back', { defaultValue: 'Quay lại' })}
                            </Button>
                        </div>

                        {campaignError && (
                            <Alert variant="destructive">
                                <AlertTitle>{t('common_error.server_error', { defaultValue: 'Có lỗi xảy ra' })}</AlertTitle>
                                <AlertDescription>{campaignError}</AlertDescription>
                            </Alert>
                        )}

                        {campaignLoading ? (
                            <div className="flex items-center justify-center rounded-md border bg-white p-8 text-muted-foreground">
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                {t('common.loading', { defaultValue: 'Đang tải...' })}
                            </div>
                        ) : (
                            <>
                                {issueCampaigns.length > 0 && (
                                    <Alert variant="destructive">
                                        <AlertTitle>
                                            {t('service_management.campaign_issue_title', { defaultValue: 'Cảnh báo chiến dịch' })}
                                        </AlertTitle>
                                        <AlertDescription>
                                            {t('service_management.campaign_issue_description', {
                                                defaultValue: 'Có {{count}} chiến dịch đang gặp lỗi/cảnh báo.',
                                                count: issueCampaigns.length,
                                            })}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <DataTable
                                    columns={campaignColumns}
                                    paginator={{
                                        data: campaigns,
                                        links: { first: null, last: null, prev: null, next: null },
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
                        )}
                    </div>
                )}
            </div>
        </>
    );
};

ServiceManagementIndex.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'menu.service_management' }]} children={page} />
);

export default ServiceManagementIndex;

