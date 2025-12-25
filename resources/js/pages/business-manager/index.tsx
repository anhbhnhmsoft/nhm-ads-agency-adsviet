import { ReactNode, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import BusinessManagerSearchForm from '@/pages/business-manager/components/search-form';
import type { BusinessManagerItem, BusinessManagerPagination, BusinessManagerStats } from '@/pages/business-manager/types/type';
import { _PlatformType } from '@/lib/types/constants';
import { Avatar, AvatarImage } from '@/components/ui/avatar';
import GoogleIcon from '@/images/google_icon.png';
import FacebookIcon from '@/images/facebook_icon.png';
import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-react';
import { router } from '@inertiajs/react';
import { service_management_index, business_managers_index } from '@/routes';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
type Props = {
    paginator: BusinessManagerPagination;
    stats?: BusinessManagerStats;
};

const BusinessManagerIndex = ({ paginator, stats }: Props) => {
    const { t } = useTranslation();
    const [selectedBM, setSelectedBM] = useState<BusinessManagerItem | null>(null);
    const [detailDialogOpen, setDetailDialogOpen] = useState(false);
    const [accounts, setAccounts] = useState<any[]>([]);
    const [loadingAccounts, setLoadingAccounts] = useState(false);
    const [selectedPlatform, setSelectedPlatform] = useState<'all' | _PlatformType>( 'all' );

    const columns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: t('business_manager.table.account_name', { defaultValue: 'Tên tài khoản' }),
                cell: ({ row }) => {
                    const displayName = row.original.config_account?.display_name || row.original.name;
                    return <span className="font-medium">{displayName}</span>;
                },
            },
            {
                accessorKey: 'id',
                header: t('business_manager.table.account_id', { defaultValue: 'ID BM' }),
            },
            {
                accessorKey: 'platform',
                header: t('business_manager.table.account_info', { defaultValue: 'Thông tin tài khoản' }),
                cell: ({ row }) => {
                    const platform = row.original.platform;
                    const totalAccounts = row.original.total_accounts;
                    const activeAccounts = row.original.active_accounts;
                    const disabledAccounts = row.original.disabled_accounts;
                    
                    return (
                        <div className="flex items-center gap-2">
                            {platform === _PlatformType.GOOGLE && (
                                <Avatar className="h-6 w-6">
                                    <AvatarImage src={GoogleIcon} />
                                </Avatar>
                            )}
                            {platform === _PlatformType.META && (
                                <Avatar className="h-6 w-6">
                                    <AvatarImage src={FacebookIcon} />
                                </Avatar>
                            )}
                            <div className="text-sm">
                                <div>Tổng: {totalAccounts}</div>
                                <div className="text-green-600">Active: {activeAccounts}</div>
                                <div className="text-red-600">Disabled: {disabledAccounts}</div>
                            </div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'owner_name',
                header: t('business_manager.table.owner', { defaultValue: 'Người sở hữu tài khoản' }),
            },
            {
                accessorKey: 'total_spend',
                header: t('business_manager.table.spend', { defaultValue: 'Chi tiêu' }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.total_spend || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })} {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_balance',
                header: t('business_manager.table.balance', { defaultValue: 'Số dư' }),
                cell: ({ row }) => {
                    const balance = parseFloat(row.original.total_balance || '0');
                    return (
                        <span className="font-medium">
                            {balance.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })} {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                id: 'actions',
                header: t('common.action', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    return (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                // Điều hướng sang trang Quản lý tài khoản, filter theo BM ID
                                router.get(service_management_index().url, {
                                    filter: {
                                        keyword: row.original.id,
                                    },
                                }, {
                                    replace: true,
                                    preserveState: false,
                                });
                            }}
                        >
                            <Eye className="h-4 w-4 mr-1" />
                            {t('common.view', { defaultValue: 'Xem tài khoản' })}
                        </Button>
                    );
                },
            },
        ],
        [t]
    );

    const accountColumns: ColumnDef<any>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('business_manager.detail.acc_name', { defaultValue: 'Acc name' }),
            },
            {
                accessorKey: 'spend_cap',
                header: t('business_manager.detail.limit', { defaultValue: 'Limit' }),
                cell: ({ row }) => {
                    const limit = row.original.spend_cap;
                    return limit ? parseFloat(limit).toLocaleString('vi-VN') : '-';
                },
            },
            {
                accessorKey: 'amount_spent',
                header: t('business_manager.detail.spend', { defaultValue: 'Spend' }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.amount_spent || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_campaigns',
                header: t('business_manager.detail.total_campaign', { defaultValue: 'Total campaign' }),
                cell: ({ row }) => {
                    return row.original.total_campaigns || 0;
                },
            },
        ],
        [t]
    );

    const platformTabs = [
        { key: 'all' as const, label: 'All', value: undefined },
        { key: _PlatformType.META, label: 'Facebook', value: _PlatformType.META },
        { key: _PlatformType.GOOGLE, label: 'Google', value: _PlatformType.GOOGLE },
    ];

    const currentStats = useMemo(() => {
        if (!stats) {
            return { total_accounts: 0, active_accounts: 0, disabled_accounts: 0 };
        }
        if (selectedPlatform === 'all') {
            return {
                total_accounts: stats.total_accounts,
                active_accounts: stats.active_accounts,
                disabled_accounts: stats.disabled_accounts,
            };
        }
        const st = stats.by_platform?.[selectedPlatform] || { total_accounts: 0, active_accounts: 0, disabled_accounts: 0 };
        return st;
    }, [stats, selectedPlatform]);

    const handleSelectPlatform = (platformKey: 'all' | _PlatformType) => {
        setSelectedPlatform(platformKey);
        const platformValue = platformKey === 'all' ? undefined : platformKey;
        router.get(business_managers_index().url, {
            filter: {
                platform: platformValue,
            },
        }, {
            replace: true,
            preserveState: true,
            only: ['paginator', 'stats'],
        });
    };

    return (
        <div>
            <h1 className="text-xl font-semibold mb-4">
                {t('business_manager.title', { defaultValue: 'Quản lý Business Manager / MCC' })}
            </h1>

            {/* Platform Tabs & Stats */}
            <div className="mb-4 flex flex-col gap-3">
                <div className="flex flex-wrap gap-2">
                    {platformTabs.map((tab) => (
                        <Button
                            key={tab.key}
                            variant={selectedPlatform === tab.key ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => handleSelectPlatform(tab.key)}
                        >
                            {tab.label}
                        </Button>
                    ))}
                </div>
                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground">
                                {t('business_manager.stats.total', { defaultValue: 'Tổng số lượng tài khoản' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {currentStats.total_accounts || 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground">
                                {t('business_manager.stats.active', { defaultValue: 'Active' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold text-green-600">
                            {currentStats.active_accounts || 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground">
                                {t('business_manager.stats.disabled', { defaultValue: 'Disabled' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold text-red-600">
                            {currentStats.disabled_accounts || 0}
                        </CardContent>
                    </Card>
                </div>
            </div>
            
            <BusinessManagerSearchForm />

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>
                        {t('business_manager.table_title', { defaultValue: 'Danh sách Business Manager / MCC' })}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <DataTable columns={columns} paginator={paginator} />
                </CardContent>
            </Card>

            {/* Dialog chi tiết BM/BCC */}
            <Dialog open={detailDialogOpen} onOpenChange={setDetailDialogOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {t('business_manager.detail.title', { defaultValue: 'Chi tiết BM/BCC' })}: {selectedBM?.name}
                        </DialogTitle>
                        <DialogDescription>
                            {t('business_manager.detail.description', { defaultValue: 'Danh sách tài khoản quảng cáo' })}
                        </DialogDescription>
                    </DialogHeader>
                    
                    {loadingAccounts ? (
                        <div className="text-center py-8">
                            {t('common.loading', { defaultValue: 'Đang tải...' })}
                        </div>
                    ) : (
                        <div className="mt-4">
                            {accounts.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    {t('business_manager.detail.no_accounts', { defaultValue: 'Chưa có tài khoản nào' })}
                                </div>
                            ) : (
                                <DataTable 
                                    columns={accountColumns} 
                                    paginator={{
                                        data: accounts,
                                        links: {
                                            first: null,
                                            last: null,
                                            next: null,
                                            prev: null,
                                        },
                                        meta: {
                                            links: [],
                                            current_page: 1,
                                            from: 1,
                                            last_page: 1,
                                            per_page: accounts.length || 1,
                                            to: accounts.length,
                                            total: accounts.length,
                                        },
                                    }} 
                                />
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
};

BusinessManagerIndex.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'business_manager.title' }]} children={page} />
);

export default BusinessManagerIndex;

