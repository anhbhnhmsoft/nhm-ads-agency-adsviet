import { DataTable } from '@/components/table/data-table';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType, _UserRole } from '@/lib/types/constants';
import BusinessManagerSearchForm from '@/pages/business-manager/components/search-form';
import type {
    BusinessManagerAccount,
    BusinessManagerItem,
    BusinessManagerPagination,
    BusinessManagerStats,
} from '@/pages/business-manager/types/type';
import { business_managers_index } from '@/routes';
import { router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Eye, EyeOff, RotateCcw } from 'lucide-react';
import { ReactNode, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { useSearchBusinessManager } from './hooks/use-search';
type ChildManagerOption = {
    id: string;
    name: string;
    parent_id: string;
};

type Props = {
    paginator: BusinessManagerPagination;
    stats?: BusinessManagerStats;
    hiddenBusinessManagers?: BusinessManagerItem[];
    childManagers?: {
        meta?: ChildManagerOption[];
        google?: ChildManagerOption[];
    };
};

const BusinessManagerIndex = ({
    paginator,
    stats,
    hiddenBusinessManagers = [],
    childManagers,
}: Props) => {
    const { t } = useTranslation();
    const [selectedBM] = useState<BusinessManagerItem | null>(null);
    const [detailDialogOpen, setDetailDialogOpen] = useState(false);
    const [accounts] = useState<BusinessManagerAccount[]>([]);
    const [loadingAccounts] = useState(false);
    const [selectedPlatform, setSelectedPlatform] = useState<
        'all' | _PlatformType | 'hidden'
    >('all');

    type AuthUser = {
        id: string;
        name: string;
        role: number;
    };

    type AuthProp = {
        user?: AuthUser;
    };

    const { props } = usePage();
    const authUser = useMemo(() => {
        const authProp = props.auth as AuthProp | AuthUser | null | undefined;
        if (authProp && typeof authProp === 'object' && 'user' in authProp) {
            return authProp.user ?? null;
        }
        return (authProp as AuthUser | null) ?? null;
    }, [props.auth]);
    const currentUserRole = authUser?.role;
    const isAdmin = currentUserRole === _UserRole.ADMIN;

    const handleHideBusinessManager = (item: BusinessManagerItem) => {
        const primaryBmId = item.bm_ids?.[0] || item.parent_bm_id;
        if (!primaryBmId || item.platform !== _PlatformType.META) {
            toast.error(
                t('business_manager.bm_not_found', {
                    defaultValue: 'Không tìm thấy BM/MCC',
                }),
            );
            return;
        }

        const displayName = item.bm_name || item.name || primaryBmId;
        const confirmed = window.confirm(
            t('business_manager.hide_confirm', {
                defaultValue:
                    'Ẩn BM/MCC {{name}} khỏi tool? Tool sẽ không lấy và không hiển thị dữ liệu của BM này nữa. Bạn có thể khôi phục ở tab BM đã ẩn.',
                name: displayName,
            }),
        );
        if (!confirmed) return;

        router.post(
            `/business-managers/${primaryBmId}/hide`,
            {},
            {
                preserveState: false,
                onSuccess: () => {
                    toast.success(
                        t('business_manager.hide_success', {
                            defaultValue: 'Đã ẩn BM/MCC',
                        }),
                    );
                },
                onError: () => {
                    toast.error(
                        t('business_manager.hide_error', {
                            defaultValue: 'Không thể ẩn BM/MCC',
                        }),
                    );
                },
            },
        );
    };

    const handleRestoreBusinessManager = (item: BusinessManagerItem) => {
        const primaryBmId = item.bm_ids?.[0] || item.id;
        if (!primaryBmId) {
            toast.error(
                t('business_manager.bm_not_found', {
                    defaultValue: 'Không tìm thấy BM/MCC',
                }),
            );
            return;
        }

        const displayName = item.bm_name || item.name || primaryBmId;
        const confirmed = window.confirm(
            t('business_manager.restore_confirm', {
                defaultValue:
                    'Khôi phục BM/MCC {{name}}? Tool sẽ hiển thị và lấy dữ liệu BM này trở lại.',
                name: displayName,
            }),
        );
        if (!confirmed) return;

        router.post(
            `/business-managers/${primaryBmId}/restore`,
            {},
            {
                preserveState: false,
                onSuccess: () => {
                    toast.success(
                        t('business_manager.restore_success', {
                            defaultValue: 'Đã khôi phục BM/MCC',
                        }),
                    );
                },
                onError: () => {
                    toast.error(
                        t('business_manager.restore_error', {
                            defaultValue: 'Không thể khôi phục BM/MCC',
                        }),
                    );
                },
            },
        );
    };

    const columns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'bm_name',
                header: t('business_manager.table.bm_name', {
                    defaultValue: 'Tên BM/MCC',
                }),
                cell: ({ row }) => {
                    const displayName =
                        row.original.bm_name ||
                        row.original.name ||
                        (row.original.bm_ids?.[0] ?? '-');
                    return <span className="font-medium">{displayName}</span>;
                },
            },
            {
                id: 'bm_ids',
                header: t('business_manager.table.bm_id', {
                    defaultValue: 'ID BM/MCC',
                }),
                cell: ({ row }) => {
                    const bmIds = row.original.bm_ids;
                    const parentBmId = row.original.parent_bm_id;
                    const platform = row.original.platform;

                    return (
                        <div className="flex flex-col gap-1">
                            <span className="text-xs text-muted-foreground">
                                {bmIds && bmIds.length ? bmIds.join(', ') : '-'}
                            </span>
                            {parentBmId && (
                                <span className="text-xs text-blue-600 dark:text-blue-400">
                                    {platform === _PlatformType.GOOGLE
                                        ? t(
                                              'business_manager.table.child_mcc',
                                              { defaultValue: 'MCC thuộc' },
                                          )
                                        : t('business_manager.table.child_bm', {
                                              defaultValue: 'BM thuộc',
                                          })}{' '}
                                    ({parentBmId})
                                </span>
                            )}
                        </div>
                    );
                },
            },
            {
                accessorKey: 'total_accounts',
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
                header: t('business_manager.table.total_accounts', {
                    defaultValue: 'Số tài khoản',
                }),
                cell: ({ row }) => {
                    const count = row.original.total_accounts ?? 0;
                    return <span className="font-medium">{count}</span>;
                },
            },
            {
                accessorKey: 'total_spend',
                header: t('business_manager.table.spend', {
                    defaultValue: 'Chi tiêu',
                }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.total_spend || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2,
                            })}{' '}
                            {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_balance',
                header: t('business_manager.table.balance', {
                    defaultValue: 'Số dư',
                }),
                cell: ({ row }) => {
                    const balance = parseFloat(
                        row.original.total_balance || '0',
                    );
                    return (
                        <span className="font-medium">
                            {balance.toLocaleString('vi-VN', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2,
                            })}{' '}
                            {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                id: 'actions',
                header: t('common.action', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    return (
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    const primaryBmId =
                                        (row.original.bm_ids &&
                                            row.original.bm_ids[0]) ||
                                        row.original.parent_bm_id;
                                    if (!primaryBmId) {
                                        toast.error(
                                            t('business_manager.bm_not_found', {
                                                defaultValue:
                                                    'Không tìm thấy BM/MCC',
                                            }),
                                        );
                                        return;
                                    }
                                    router.get(
                                        '/service-management',
                                        {
                                            filter: {
                                                manager_id: primaryBmId,
                                                platform: row.original.platform,
                                                child_manager_id: primaryBmId,
                                            },
                                        },
                                        {
                                            replace: true,
                                            preserveState: false,
                                        },
                                    );
                                }}
                            >
                                <Eye className="mr-1 h-4 w-4" />
                                {t('common.view_account', {
                                    defaultValue: 'Xem tài khoản',
                                })}
                            </Button>
                            {isAdmin &&
                                row.original.platform ===
                                    _PlatformType.META && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            handleHideBusinessManager(
                                                row.original,
                                            )
                                        }
                                        title={t(
                                            'business_manager.hide_tooltip',
                                            {
                                                defaultValue:
                                                    'Ẩn BM này khỏi dữ liệu đồng bộ và danh sách hiển thị',
                                            },
                                        )}
                                    >
                                        <EyeOff className="mr-1 h-4 w-4" />
                                        {t('business_manager.hide_from_tool', {
                                            defaultValue: 'Ẩn hiển thị',
                                        })}
                                    </Button>
                                )}
                        </div>
                    );
                },
            },
        ],
        [t, isAdmin],
    );

    const hiddenColumns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'bm_name',
                header: t('business_manager.table.bm_name', {
                    defaultValue: 'Tên BM/MCC',
                }),
                cell: ({ row }) => (
                    <span className="font-medium">
                        {row.original.bm_name || row.original.name || '-'}
                    </span>
                ),
            },
            {
                id: 'bm_ids',
                header: t('business_manager.table.bm_id', {
                    defaultValue: 'ID BM/MCC',
                }),
                cell: ({ row }) =>
                    row.original.bm_ids?.join(', ') || row.original.id || '-',
            },
            {
                accessorKey: 'hidden_at',
                header: t('business_manager.hidden_at', {
                    defaultValue: 'Ngày ẩn',
                }),
                cell: ({ row }) => {
                    if (!row.original.hidden_at) return '-';
                    const date = new Date(row.original.hidden_at);
                    return Number.isNaN(date.getTime())
                        ? row.original.hidden_at
                        : date.toLocaleString('vi-VN');
                },
            },
            {
                id: 'actions',
                header: t('common.action', { defaultValue: 'Hành động' }),
                cell: ({ row }) => (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            handleRestoreBusinessManager(row.original)
                        }
                    >
                        <RotateCcw className="mr-1 h-4 w-4" />
                        {t('business_manager.restore', {
                            defaultValue: 'Khôi phục',
                        })}
                    </Button>
                ),
            },
        ],
        [t],
    );

    const accountColumns: ColumnDef<BusinessManagerAccount>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('business_manager.detail.acc_name', {
                    defaultValue: 'Acc name',
                }),
            },
            {
                accessorKey: 'spend_cap',
                header: t('business_manager.detail.limit', {
                    defaultValue: 'Limit',
                }),
                cell: ({ row }) => {
                    const limit = row.original.spend_cap;
                    return limit
                        ? parseFloat(limit).toLocaleString('vi-VN')
                        : '-';
                },
            },
            {
                accessorKey: 'amount_spent',
                header: t('business_manager.detail.spend', {
                    defaultValue: 'Spend',
                }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.amount_spent || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_campaigns',
                header: t('business_manager.detail.total_campaign', {
                    defaultValue: 'Total campaign',
                }),
                cell: ({ row }) => {
                    return row.original.total_campaigns || 0;
                },
            },
        ],
        [t],
    );

    const platformTabs = [
        { key: 'all' as const, label: 'All', value: undefined },
        {
            key: _PlatformType.META,
            label: 'Facebook',
            value: _PlatformType.META,
        },
        {
            key: _PlatformType.GOOGLE,
            label: 'Google',
            value: _PlatformType.GOOGLE,
        },
        ...(isAdmin
            ? [
                  {
                      key: 'hidden' as const,
                      label: `${t('business_manager.hidden_tab', { defaultValue: 'BM đã ẩn' })} (${hiddenBusinessManagers.length})`,
                      value: undefined,
                  },
              ]
            : []),
    ];

    const currentStats = useMemo(() => {
        if (!stats) {
            return {
                total_accounts: 0,
                active_accounts: 0,
                disabled_accounts: 0,
            };
        }
        if (selectedPlatform === 'all' || selectedPlatform === 'hidden') {
            return {
                total_accounts: stats.total_accounts,
                active_accounts: stats.active_accounts,
                disabled_accounts: stats.disabled_accounts,
            };
        }
        const st = stats.by_platform?.[selectedPlatform] || {
            total_accounts: 0,
            active_accounts: 0,
            disabled_accounts: 0,
        };
        return st;
    }, [stats, selectedPlatform]);

    const handleSelectPlatform = (
        platformKey: 'all' | _PlatformType | 'hidden',
    ) => {
        setSelectedPlatform(platformKey);
        if (platformKey === 'hidden') {
            return;
        }

        const platformValue = platformKey === 'all' ? undefined : platformKey;
        router.get(
            business_managers_index().url,
            {
                filter: {
                    platform: platformValue,
                },
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'stats'],
            },
        );
    };

    const { query, setQuery, handleSearch, handleReset } =
        useSearchBusinessManager();

    return (
        <div>
            <h1 className="mb-4 text-xl font-semibold">
                {t('business_manager.title', {
                    defaultValue: 'Quản lý Business Manager / MCC',
                })}
            </h1>

            <div className="mb-4 flex flex-wrap gap-2">
                {platformTabs.map((tab) => (
                    <Button
                        key={tab.key}
                        variant={
                            selectedPlatform === tab.key ? 'default' : 'outline'
                        }
                        size="sm"
                        onClick={() => handleSelectPlatform(tab.key)}
                    >
                        {tab.label}
                    </Button>
                ))}
            </div>

            {selectedPlatform !== 'hidden' && (
                <BusinessManagerSearchForm
                    query={query}
                    setQuery={setQuery}
                    handleSearch={handleSearch}
                    handleReset={handleReset}
                />
            )}

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>
                        {selectedPlatform === 'hidden'
                            ? t('business_manager.hidden_table_title', {
                                  defaultValue: 'Danh sách BM/MCC đã ẩn',
                              })
                            : t('business_manager.table_title', {
                                  defaultValue:
                                      'Danh sách Business Manager / MCC',
                              })}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {selectedPlatform === 'hidden' ? (
                        <DataTable
                            columns={hiddenColumns}
                            paginator={{
                                data: hiddenBusinessManagers,
                                links: {
                                    first: null,
                                    last: null,
                                    prev: null,
                                    next: null,
                                },
                                meta: {
                                    links: [],
                                    current_page: 1,
                                    from: hiddenBusinessManagers.length ? 1 : 0,
                                    last_page: 1,
                                    per_page:
                                        hiddenBusinessManagers.length || 1,
                                    to: hiddenBusinessManagers.length,
                                    total: hiddenBusinessManagers.length,
                                },
                            }}
                        />
                    ) : (
                        <DataTable columns={columns} paginator={paginator} />
                    )}
                </CardContent>
            </Card>

            {/* Dialog chi tiết BM/BCC */}
            <Dialog open={detailDialogOpen} onOpenChange={setDetailDialogOpen}>
                <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {t('business_manager.detail.title', {
                                defaultValue: 'Chi tiết BM/BCC',
                            })}
                            : {selectedBM?.name}
                        </DialogTitle>
                        <DialogDescription>
                            {t('business_manager.detail.description', {
                                defaultValue: 'Danh sách tài khoản quảng cáo',
                            })}
                        </DialogDescription>
                    </DialogHeader>

                    {loadingAccounts ? (
                        <div className="py-8 text-center">
                            {t('common.loading', {
                                defaultValue: 'Đang tải...',
                            })}
                        </div>
                    ) : (
                        <div className="mt-4">
                            {accounts.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    {t('business_manager.detail.no_accounts', {
                                        defaultValue: 'Chưa có tài khoản nào',
                                    })}
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
    <AppLayout
        breadcrumbs={[{ title: 'business_manager.title' }]}
        children={page}
    />
);

export default BusinessManagerIndex;
