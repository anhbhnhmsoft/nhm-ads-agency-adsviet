import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TransactionList } from '@/components/transactions/transaction-list';
import { cn } from '@/lib/utils';
import useCheckRole from '@/hooks/use-check-role';
import AppLayout from '@/layouts/app-layout';
import { _UserRole, userRolesLabel } from '@/lib/types/constants';
import { IBreadcrumbItem, type IUser } from '@/lib/types/type';
import { dashboard, wallet_index } from '@/routes';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    Clock3,
    Eye,
    EyeOff,
    TrendingDown,
    TrendingUp,
    UserCheck,
    Users,
    Wallet,
} from 'lucide-react';
import { type ReactNode, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { WalletTransaction } from '@/pages/wallet/types/type';
import type { AdminDashboardData, AdminPendingTransaction, AdminPendingTransactions, DashboardData } from './types';

const breadcrumbs: IBreadcrumbItem[] = [
    {
        title: 'menu.dashboard',
        href: dashboard().url,
    },
];

type Props = {
    dashboardData?: DashboardData | null;
    adminDashboardData?: AdminDashboardData | null;
    adminPendingTransactions?: AdminPendingTransactions | null;
    dashboardError?: string | null;
    selectedPlatform?: string; // 'meta' hoặc 'google_ads'
};

export default function Index({ dashboardData, adminDashboardData, adminPendingTransactions, dashboardError, selectedPlatform = 'meta' }: Props) {
    const { t } = useTranslation();
    const { props } = usePage();
    const authUser = useMemo(() => {
        const authProp = props.auth as { user?: IUser | null } | IUser | null | undefined;
        if (authProp && typeof authProp === 'object' && 'user' in authProp) {
            return authProp.user ?? null;
        }
        return (authProp as IUser | null) ?? null;
    }, [props.auth]);
    const checkRole = useCheckRole(authUser);
    const [showBalance, setShowBalance] = useState(true);
    const [platform, setPlatform] = useState<string>(selectedPlatform);
    const [approveLoadingId, setApproveLoadingId] = useState<string | null>(null);
    
    // Đồng bộ platform state với props khi selectedPlatform thay đổi
    useEffect(() => {
        setPlatform(selectedPlatform);
    }, [selectedPlatform]);
    const [cancelLoadingId, setCancelLoadingId] = useState<string | null>(null);
    const [showWithdrawInfo, setShowWithdrawInfo] = useState(false);
    const [selectedWithdrawInfo, setSelectedWithdrawInfo] = useState<{
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
    } | null>(null);

    const currencyFormatter = useMemo(
        () =>
            new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }),
        []
    );

    const formatCurrency = (value: string | number) => {
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return currencyFormatter.format(num);
    };

    const formatPercent = (value: number) => {
        return `${value >= 0 ? '+' : ''}${value.toFixed(1)}%`;
    };

    const handleViewWithdrawInfo = (
        info?: { bank_name?: string; account_holder?: string; account_number?: string } | null
    ) => {
        setSelectedWithdrawInfo(info ?? null);
        setShowWithdrawInfo(true);
    };

    const handlePlatformChange = (value: string) => {
        setPlatform(value);
        // Reload dashboard với platform mới
        router.get(dashboard().url, { platform: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const isAgencyOrCustomer = checkRole([_UserRole.AGENCY, _UserRole.CUSTOMER]);
    const isAdminOrStaff = checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE]);
    const currentUser = authUser;

    if (isAdminOrStaff && adminDashboardData) {
        const roleLabelKey = currentUser ? userRolesLabel[currentUser.role] : undefined;
        const roleLabel = roleLabelKey ? t(roleLabelKey) : '';
        const pendingPagination: AdminPendingTransactions | null = adminPendingTransactions ?? null;
        const pendingTransactionsForList: WalletTransaction[] = (pendingPagination?.data ?? []).map((tx: AdminPendingTransaction) => ({
            id: tx.id,
            amount: tx.amount,
            type: tx.type,
            status: tx.status,
            description: tx.description ?? undefined,
            network: tx.network ?? undefined,
            createdAt: tx.created_at ?? undefined,
            withdraw_info: tx.withdraw_info ?? null,
            user:
                tx.customer_name || tx.customer_email || tx.customer_id
                    ? {
                          id: tx.customer_id !== undefined && tx.customer_id !== null
                              ? String(tx.customer_id)
                              : tx.customer_email || tx.id,
                          name: tx.customer_name || tx.customer_email || '',
                      }
                    : undefined,
        }));
        const pendingMeta = pendingPagination?.meta;
        const canApproveTransactions = true;

        const handleApprove = (transactionId: string) => {
            if (!window.confirm(t('transactions.confirm_approve', { defaultValue: 'Xác nhận duyệt giao dịch này?' }))) {
                return;
            }
            setApproveLoadingId(transactionId);
            router.post(
                `/transactions/${transactionId}/approve`,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setApproveLoadingId(null);
                    },
                    onSuccess: () => {
                        router.reload({ only: ['adminDashboardData', 'adminPendingTransactions'] });
                    },
                }
            );
        };

        const handleCancel = (transactionId: string) => {
            if (!window.confirm(t('transactions.confirm_cancel', { defaultValue: 'Xác nhận hủy giao dịch này?' }))) {
                return;
            }
            setCancelLoadingId(transactionId);
            router.post(
                `/transactions/${transactionId}/cancel`,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setCancelLoadingId(null);
                    },
                    onSuccess: () => {
                        router.reload({ only: ['adminDashboardData', 'adminPendingTransactions'] });
                    },
                }
            );
        };

        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={t('dashboard.admin_title')} />
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                    <div>
                        <h1 className="text-2xl font-bold">{t('dashboard.admin_title')}</h1>
                        <p className="text-muted-foreground">
                            {t('dashboard.admin_subtitle', {
                                name: currentUser?.name || 'Admin',
                                role: roleLabel || 'Admin',
                            })}
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <AdminStatCard
                            label={t('dashboard.admin_total_customers')}
                            value={adminDashboardData.total_customers}
                            icon={<Users className="h-5 w-5 text-blue-600" />}
                        />
                        <AdminStatCard
                            label={t('dashboard.admin_active_customers')}
                            value={adminDashboardData.active_customers}
                            icon={<UserCheck className="h-5 w-5 text-green-600" />}
                        />
                        <AdminStatCard
                            label={t('dashboard.admin_pending_transactions')}
                            value={adminDashboardData.pending_transactions}
                            icon={<Clock3 className="h-5 w-5 text-amber-500" />}
                        />
                    </div>

                    <Card>
                        <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-base font-semibold">
                                {t('dashboard.admin_pending_transactions_title')}
                            </CardTitle>
                            <div className="text-sm text-muted-foreground">
                                {adminDashboardData.pending_transactions} {t('transactions.items')}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <TransactionList
                                transactions={pendingTransactionsForList}
                                canApprove={canApproveTransactions}
                                onApprove={handleApprove}
                                onCancel={handleCancel}
                                approveLoadingId={approveLoadingId}
                                cancelLoadingId={cancelLoadingId}
                                showExplorerLink={false}
                                emptyMessage={t('dashboard.admin_no_transactions')}
                                onViewWithdrawInfo={handleViewWithdrawInfo}
                            />
                            {pendingMeta && pendingMeta.last_page > 1 && (
                                <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="text-sm text-gray-600">
                                        {t('common.showing', {
                                            defaultValue: 'Hiển thị {{from}} đến {{to}} trong tổng số {{total}}',
                                            from: pendingMeta.from ?? 0,
                                            to: pendingMeta.to ?? 0,
                                            total: pendingMeta.total,
                                        })}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={pendingMeta.current_page === 1}
                                            onClick={() =>
                                                router.get(
                                                    dashboard().url,
                                                    { pending_page: pendingMeta.current_page - 1 },
                                                    { preserveScroll: true, preserveState: true }
                                                )
                                            }
                                        >
                                            {t('common.previous', { defaultValue: 'Trước' })}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={pendingMeta.current_page === pendingMeta.last_page}
                                            onClick={() =>
                                                router.get(
                                                    dashboard().url,
                                                    { pending_page: pendingMeta.current_page + 1 },
                                                    { preserveScroll: true, preserveState: true }
                                                )
                                            }
                                        >
                                            {t('common.next', { defaultValue: 'Sau' })}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Dialog open={showWithdrawInfo} onOpenChange={setShowWithdrawInfo}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('transactions.withdraw_info_title', { defaultValue: 'Thông tin rút tiền' })}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('transactions.withdraw_info_description', {
                                        defaultValue: 'Thông tin tài khoản người dùng đã nhập',
                                    })}
                                </DialogDescription>
                            </DialogHeader>
                            {selectedWithdrawInfo && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>{t('service_user.bank_name', { defaultValue: 'Tên Ngân hàng/Ví điện tử' })}</Label>
                                        <div className="rounded-md border p-2 text-sm">
                                            {selectedWithdrawInfo.bank_name || '-'}
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{t('service_user.account_holder', { defaultValue: 'Tên Chủ tài khoản/Ví' })}</Label>
                                        <div className="rounded-md border p-2 text-sm">
                                            {selectedWithdrawInfo.account_holder || '-'}
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{t('service_user.account_number', { defaultValue: 'Số Tài khoản/Số điện thoại ví' })}</Label>
                                        <div className="rounded-md border p-2 text-sm">
                                            {selectedWithdrawInfo.account_number || '-'}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </DialogContent>
                    </Dialog>

                    {dashboardError && (
                        <Card className="border-yellow-500 bg-yellow-50 dark:bg-yellow-950/20">
                            <CardContent className="pt-6">
                                <div className="flex items-center gap-2 text-yellow-600">
                                    <AlertCircle className="h-5 w-5" />
                                    <span>{dashboardError}</span>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppLayout>
        );
    }

    if (!isAgencyOrCustomer || !dashboardData) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={t('dashboard.title')} />
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="text-center text-muted-foreground py-12">
                        {t('dashboard.coming_soon')}
                    </div>
                </div>
            </AppLayout>
        );
    }

    const walletBalance = showBalance ? formatCurrency(dashboardData.wallet.balance) : '••••••';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard.title')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                {/* Top Header - Wallet Balance */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Wallet className="h-5 w-5 text-muted-foreground" />
                                <CardTitle className="sm:text-lg text-base">{t('dashboard.wallet_balance')}</CardTitle>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setShowBalance(!showBalance)}
                            >
                                {showBalance ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="sm:flex items-center justify-between">
                            <div className="text-3xl font-bold">{walletBalance}</div>
                            <div className="flex sm:mt-0 mt-4 gap-2">
                                <Button asChild size="sm">
                                    <Link href={wallet_index().url}>{t('service_user.title')}</Link>
                                </Button>
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/transactions">{t('dashboard.transactions')}</Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
{isAgencyOrCustomer && (
                            <div className="mt-4 pt-4 border-t">
                                <Label className="text-sm text-muted-foreground mb-2 block">
                                    {t('dashboard.select_platform', { defaultValue: 'Chọn nền tảng' })}
                                </Label>
                                <Select value={platform} onValueChange={handlePlatformChange}>
                                    <SelectTrigger className="w-full sm:w-[200px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="meta">
                                            {t('dashboard.platform_meta', { defaultValue: 'Meta Ads' })}
                                        </SelectItem>
                                        <SelectItem value="google_ads">
                                            {t('dashboard.platform_google_ads', { defaultValue: 'Google Ads' })}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Tất cả tài khoản */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">{t('dashboard.all_accounts')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{dashboardData.overview.total_accounts}</div>
                            <div className="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                                <div className="flex items-center gap-1">
                                    <div className="h-2 w-2 rounded-full bg-green-500" />
                                    <span>{dashboardData.overview.active_accounts} {t('dashboard.active')}</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <div className="h-2 w-2 rounded-full bg-red-500" />
                                    <span>{dashboardData.overview.paused_accounts} {t('dashboard.paused')}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tổng chỉ tiêu */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">{t('dashboard.total_spend')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(dashboardData.overview.total_spend)}</div>
                            <div className="text-sm text-muted-foreground mt-1">
                                {t('dashboard.today_spend_label')}: {formatCurrency(dashboardData.overview.today_spend)}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Dịch vụ */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">{t('dashboard.services')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{dashboardData.overview.total_services}</div>
                            <div className="text-sm text-muted-foreground mt-1">
                                {t('dashboard.available_services')}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Cảnh báo nghiêm trọng */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">{t('dashboard.critical_alerts')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{dashboardData.overview.critical_alerts}</div>
                            <div className="text-sm text-muted-foreground mt-1">
                                {dashboardData.overview.accounts_with_errors} {t('dashboard.total_errors')}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Metrics thời gian thực */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">{t('dashboard.real_time_metrics')}</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <MetricCard
                            title={t('dashboard.total_spend')}
                            value={formatCurrency(dashboardData.metrics.total_spend.value)}
                        />
                        <MetricCard
                            title={t('dashboard.today_spend')}
                            value={formatCurrency(dashboardData.metrics.today_spend.value)}
                            percentChange={dashboardData.metrics.today_spend.percent_change}
                        />
                        <MetricCard
                            title={t('dashboard.total_impressions')}
                            value={dashboardData.metrics.total_impressions.value}
                            percentChange={dashboardData.metrics.total_impressions.percent_change}
                        />
                        <MetricCard
                            title={t('dashboard.total_clicks')}
                            value={dashboardData.metrics.total_clicks.value}
                            percentChange={dashboardData.metrics.total_clicks.percent_change}
                        />
                        <MetricCard
                            title={t('dashboard.total_conversions')}
                            value={dashboardData.metrics.total_conversions.value.toString()}
                            percentChange={dashboardData.metrics.total_conversions.percent_change}
                        />
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">{t('dashboard.active_accounts')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {dashboardData.metrics.active_accounts.active}
                                </div>
                                <div className="text-sm text-muted-foreground mt-1">
                                    / {dashboardData.metrics.active_accounts.total} {t('dashboard.total')}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Hiệu suất trung bình và Budget Usage */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Hiệu suất trung bình */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('dashboard.average_performance')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm text-muted-foreground mb-1">{t('dashboard.available_services')}</div>
                                <div className="text-2xl font-bold">{dashboardData.performance.conversion_rate}%</div>
                            </div>
                            <div>
                                <div className="text-sm text-muted-foreground mb-1">{t('dashboard.avg_cpc')}</div>
                                <div className="text-2xl font-bold">{formatCurrency(dashboardData.performance.avg_cpc)}</div>
                            </div>
                            <div>
                                <div className="text-sm text-muted-foreground mb-1">{t('dashboard.avg_roas')}</div>
                                <div className="text-2xl font-bold">{dashboardData.performance.avg_roas}x</div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Sử dụng ngân sách */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('dashboard.budget_usage')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {(() => {
                                const budget = dashboardData.budget as DashboardData['budget'];
                                const isPostpay = Boolean(budget.is_postpay);
                                if (isPostpay) {
                                    return (
                                        <div className="space-y-2">
                                            <div className="text-sm text-muted-foreground">
                                                {t('dashboard.budget_postpay_hint')}
                                            </div>
                                            <div className="text-2xl font-bold">
                                                {formatCurrency(budget.used)}
                                            </div>
                                        </div>
                                    );
                                }
                                return (
                                    <>
                                        <div className="mb-4">
                                            <div className="text-sm text-muted-foreground mb-1">{t('dashboard.today_spend')}</div>
                                            <div className="text-2xl font-bold">
                                                {formatCurrency(budget.used)} / {formatCurrency(budget.total)}
                                            </div>
                                        </div>
                                        <Progress value={parseFloat(budget.usage_percent)} className="mb-2" />
                                        <div className="flex items-center justify_between text-sm">
                                            <span className="text-muted-foreground">
                                                {budget.usage_percent}% {t('dashboard.used')}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatCurrency(budget.remaining)} {t('dashboard.remaining')}
                                            </span>
                                        </div>
                                    </>
                                );
                            })()}
                        </CardContent>
                    </Card>
                </div>

                {/* Vấn đề nghiêm trọng */}
                {dashboardData.alerts.critical_errors > 0 && (
                    <Card className="border-red-500 bg-red-50 dark:bg-red-950/20">
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-red-600" />
                                <CardTitle className="text-red-600">{t('dashboard.critical_issues')}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <div className="text-sm text-muted-foreground mb-1">{t('dashboard.critical_errors')}</div>
                                    <div className="text-2xl font-bold text-red-600">{dashboardData.alerts.critical_errors}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground mb-1">{t('dashboard.accounts_with_errors')}</div>
                                    <div className="text-2xl font-bold text-red-600">{dashboardData.alerts.accounts_with_errors}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {dashboardError && (
                    <Card className="border-yellow-500 bg-yellow-50 dark:bg-yellow-950/20">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-2 text-yellow-600">
                                <AlertCircle className="h-5 w-5" />
                                <span>{dashboardError}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

type AdminStatCardProps = {
    label: string;
    value: number;
    icon: ReactNode;
};

function AdminStatCard({ label, value, icon }: AdminStatCardProps) {
    return (
        <Card>
            <CardContent className="flex items-center justify-between py-6">
                <div>
                    <div className="text-sm text-muted-foreground">{label}</div>
                    <div className="text-3xl font-bold">{value}</div>
                </div>
                <div className="rounded-full bg-muted p-3">{icon}</div>
            </CardContent>
        </Card>
    );
}

type MetricCardProps = {
    title: string;
    value: string;
    percentChange?: number;
};

function MetricCard({ title, value, percentChange }: MetricCardProps) {
    const showTrend = typeof percentChange === 'number';
    const safePercent = percentChange ?? 0;
    const isPositive = safePercent >= 0;
    const Icon = isPositive ? TrendingUp : TrendingDown;

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {showTrend && (
                    <div className={`flex items-center gap-1 mt-1 text-sm ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                        <Icon className="h-4 w-4" />
                        <span>{safePercent >= 0 ? '+' : ''}{safePercent.toFixed(1)}%</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
