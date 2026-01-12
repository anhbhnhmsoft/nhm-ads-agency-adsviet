import { ReactNode, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import BusinessManagerSearchForm from '@/pages/business-manager/components/search-form';
import type { BusinessManagerItem, BusinessManagerPagination, BusinessManagerStats, BusinessManagerAccount } from '@/pages/business-manager/types/type';
import { _PlatformType } from '@/lib/types/constants';
import { Avatar, AvatarImage } from '@/components/ui/avatar';
import GoogleIcon from '@/images/google_icon.png';
import FacebookIcon from '@/images/facebook_icon.png';
import { Button } from '@/components/ui/button';
import { Eye, Wallet } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { toast } from 'sonner';
import { _UserRole } from '@/lib/types/constants';
import { usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from 'axios';
import {
    wallet_me_json,
    business_managers_index,
    business_managers_get_accounts,
    meta_get_campaigns,
    google_ads_get_campaigns,
    meta_update_campaign_spend_cap,
    google_ads_update_campaign_budget,
} from '@/routes';
type Props = {
    paginator: BusinessManagerPagination;
    stats?: BusinessManagerStats;
};

const BusinessManagerIndex = ({ paginator, stats }: Props) => {
    const { t } = useTranslation();
    const [selectedBM] = useState<BusinessManagerItem | null>(null);
    const [detailDialogOpen, setDetailDialogOpen] = useState(false);
    const [accounts] = useState<BusinessManagerAccount[]>([]);
    const [loadingAccounts] = useState(false);
    const [selectedPlatform, setSelectedPlatform] = useState<'all' | _PlatformType>('all');

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

    // State cho dialog nạp tiền - Tái sử dụng form Cập nhật ngân sách
    const [topUpDialogOpen, setTopUpDialogOpen] = useState(false);
    const [selectedBMForTopUp, setSelectedBMForTopUp] = useState<BusinessManagerItem | null>(null);
    const [topUpAmount, setTopUpAmount] = useState('');
    const [topUpWalletPassword, setTopUpWalletPassword] = useState('');
    const [topUpSubmitting, setTopUpSubmitting] = useState(false);
    const [walletBalance, setWalletBalance] = useState<number | null>(null);
    const [walletBalanceLoading, setWalletBalanceLoading] = useState(false);

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
    const isAgencyOrCustomer = currentUserRole === _UserRole.AGENCY || currentUserRole === _UserRole.CUSTOMER;

    const columns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('business_manager.table.account_name', { defaultValue: 'Tên tài khoản' }),
                cell: ({ row }) => {
                    const displayName = row.original.account_name || row.original.name;
                    return <span className="font-medium">{displayName}</span>;
                },
            },
            {
                accessorKey: 'account_id',
                header: t('business_manager.table.account_id', { defaultValue: 'ID tài khoản' }),
            },
            {
                id: 'bm_ids',
                header: t('business_manager.table.bm_id', { defaultValue: 'ID BM' }),
                cell: ({ row }) => {
                    const bmIds = row.original.bm_ids;
                    return (
                        <span className="text-xs text-muted-foreground">
                            {bmIds && bmIds.length ? bmIds.join(', ') : '-'}
                        </span>
                    );
                },
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
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={async () => {
                                    setSelectedBMForTopUp(row.original);
                                    setTopUpDialogOpen(true);

                                    // Chỉ lấy số dư ví cho role Agency/Customer
                                    if (isAgencyOrCustomer && walletBalance === null && !walletBalanceLoading) {
                                        try {
                                            setWalletBalanceLoading(true);
                                            const response = await axios.get(wallet_me_json().url);
                                            const balance = response?.data?.data?.balance;
                                            const parsedBalance = parseNumber(balance);
                                            setWalletBalance(parsedBalance);
                                        } catch (e) {
                                            console.error('Error fetching wallet balance:', e);
                                            setWalletBalance(null);
                                        } finally {
                                            setWalletBalanceLoading(false);
                                        }
                                    }
                                }}
                            >
                                <Wallet className="h-4 w-4 mr-1" />
                                {t('business_manager.top_up', { defaultValue: 'Nạp tiền' })}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    if (!row.original.service_user_id) {
                                        toast.error(t('business_manager.account_not_assigned', { 
                                            defaultValue: 'Tài khoản này chưa được gán với user nào' 
                                        }));
                                        return;
                                    }
                                    router.get('/service-management', {
                                        filter: {
                                            service_user_id: row.original.service_user_id,
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
                        </div>
                    );
                },
            },
        ],
        [t]
    );

    const accountColumns: ColumnDef<BusinessManagerAccount>[] = useMemo(
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

            <Dialog open={topUpDialogOpen} onOpenChange={setTopUpDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <span>{t('business_manager.top_up_dialog.title', {
                                defaultValue: 'Nạp tiền vào BM/MCC',
                                name: selectedBMForTopUp?.name || selectedBMForTopUp?.id
                            })}</span>
                            {selectedBMForTopUp?.platform === _PlatformType.META && (
                                <span
                                    className="text-xs text-muted-foreground"
                                    title={t('service_management.campaign_update_budget_help_meta_tooltip')}
                                >
                                    ⓘ
                                </span>
                            )}
                            {selectedBMForTopUp?.platform === _PlatformType.GOOGLE && (
                                <span
                                    className="text-xs text-muted-foreground"
                                    title={t('service_management.campaign_update_budget_help_google_tooltip')}
                                >
                                    ⓘ
                                </span>
                            )}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedBMForTopUp?.platform === _PlatformType.META &&
                                t('service_management.campaign_update_budget_help_meta')}
                            {selectedBMForTopUp?.platform === _PlatformType.GOOGLE &&
                                t('service_management.campaign_update_budget_help_google')}
                            {!selectedBMForTopUp?.platform &&
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
                            <Label htmlFor="top-up-amount">
                                {t('service_management.campaign_update_budget_amount_label')}
                            </Label>
                            <Input
                                id="top-up-amount"
                                type="number"
                                min={0}
                                step="0.01"
                                value={topUpAmount}
                                onChange={(e) => setTopUpAmount(e.target.value)}
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('service_management.campaign_update_budget_min_hint', {
                                    amount: 100,
                                })}
                            </p>
                        </div>
                        {isAgencyOrCustomer && (
                            <div className="space-y-1">
                                <Label htmlFor="top-up-wallet-password">
                                    {t('service_management.campaign_update_budget_wallet_password_label')}
                                </Label>
                                <Input
                                    id="top-up-wallet-password"
                                    type="password"
                                    value={topUpWalletPassword}
                                    onChange={(e) => setTopUpWalletPassword(e.target.value)}
                                />
                            </div>
                        )}
                    </div>
                    <DialogFooter className="pt-4">
                        <Button
                            variant="outline"
                            onClick={() => {
                                if (!topUpSubmitting) {
                                    setTopUpDialogOpen(false);
                                    setTopUpAmount('');
                                    setTopUpWalletPassword('');
                                    setSelectedBMForTopUp(null);
                                    setWalletBalance(null);
                                }
                            }}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            onClick={async () => {
                                if (!topUpAmount || Number(topUpAmount) <= 0) {
                                    toast.error(
                                        t('common_validation.amount_required') || 'Amount is invalid',
                                    );
                                    return;
                                }

                                const amountNumber = Number(topUpAmount);
                                if (amountNumber < 100) {
                                    toast.error(
                                        t('service_management.campaign_update_budget_min_error', {
                                            amount: 100,
                                        }),
                                    );
                                    return;
                                }

                                // Với Agency/Customer thì bắt buộc nhập mật khẩu ví
                                if (isAgencyOrCustomer && !topUpWalletPassword) {
                                    toast.error(
                                        t('service_management.campaign_update_budget_wallet_password_required'),
                                    );
                                    return;
                                }

                                try {
                                    setTopUpSubmitting(true);
                                    const platformType = selectedBMForTopUp?.platform ?? null;
                                    if (!selectedBMForTopUp || !platformType) {
                                        toast.error(t('service_management.unsupported_platform'));
                                        return;
                                    }

                                    const accountsResponse = await axios.get(
                                        business_managers_get_accounts({ bmId: selectedBMForTopUp.id }).url,
                                        { params: { platform: platformType } }
                                    );

                                    const accounts = accountsResponse?.data?.data || [];
                                    if (accounts.length === 0) {
                                        toast.error(t('business_manager.top_up_dialog.no_accounts'));
                                        return;
                                    }

                                    const firstAccount = accounts[0];
                                    const serviceUserId = firstAccount.service_user_id;
                                    const accountId = firstAccount.id || firstAccount.account_id;

                                    if (!serviceUserId || !accountId) {
                                        toast.error(t('business_manager.top_up_dialog.account_not_found'));
                                        return;
                                    }

                                    const campaignsResponse = await axios.get(
                                        platformType === _PlatformType.META
                                            ? meta_get_campaigns({ serviceUserId, accountId }).url
                                            : google_ads_get_campaigns({ serviceUserId, accountId }).url,
                                        { params: { per_page: 1 } }
                                    );

                                    const campaigns = campaignsResponse?.data?.data?.data || campaignsResponse?.data?.data || [];
                                    if (campaigns.length === 0) {
                                        toast.error(t('service_management.campaign_not_selected'));
                                        return;
                                    }

                                    const campaign = campaigns[0];
                                    const campaignId = campaign.id || campaign.campaign_id;

                                    if (!campaignId) {
                                        toast.error(t('service_management.campaign_not_selected'));
                                        return;
                                    }

                                    if (platformType === _PlatformType.META) {
                                        await axios.post(
                                            meta_update_campaign_spend_cap({ serviceUserId, campaignId }).url,
                                            {
                                                amount: amountNumber,
                                            },
                                        );
                                    } else if (platformType === _PlatformType.GOOGLE) {
                                        await axios.post(
                                            google_ads_update_campaign_budget({ serviceUserId, campaignId }).url,
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

                                    router.reload({ only: ['paginator', 'stats'] });
                                    setTopUpDialogOpen(false);
                                    setTopUpAmount('');
                                    setTopUpWalletPassword('');
                                    setWalletBalance(null);
                                } catch (error) {
                                    const axiosError = error as { response?: { data?: { message?: string } } };
                                    const message =
                                        axiosError?.response?.data?.message ||
                                        t('service_management.campaign_update_budget_insufficient_balance');
                                    toast.error(message);
                                } finally {
                                    setTopUpSubmitting(false);
                                }
                            }}
                            disabled={topUpSubmitting}
                        >
                            {topUpSubmitting
                                ? t('common.processing')
                                : t('business_manager.top_up_dialog.submit', { defaultValue: 'Gửi yêu cầu' })}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

BusinessManagerIndex.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'business_manager.title' }]} children={page} />
);

export default BusinessManagerIndex;

