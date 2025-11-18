    import React, { ReactNode, useState } from 'react';
    import AppLayout from '@/layouts/app-layout';
    import { useTranslation } from 'react-i18next';
    import { router, useForm, Head } from '@inertiajs/react';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        ArrowDownCircle,
        ArrowUpCircle,
        Clock,
        ExternalLink,
        Gift,
        Loader2,
        Percent,
        RefreshCcw,
        Wallet as WalletIcon,
        Ban,
        CheckCircle2,
        XCircle,
        Search,
        Filter,
    } from 'lucide-react';
    import { cn } from '@/lib/utils';
    import { TRANSACTION_TYPE, TRANSACTION_STATUS, TRANSACTION_TYPE_MAP, TRANSACTION_STATUS_MAP } from '@/pages/wallet/types/constants';
    import type { TransactionsIndexProps } from './types/type';

    const formatter = new Intl.NumberFormat('vi-VN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const TransactionsIndex = ({ transactions, pagination, filters, canApprove }: TransactionsIndexProps) => {
        const { t } = useTranslation();
        const [showFilters, setShowFilters] = useState(false);

        const transactionsList = transactions?.data || [];

        const filterForm = useForm({
            type: filters.type || '',
            status: filters.status || '',
            user_id: filters.user_id || '',
        });

        const approveForm = useForm<{ tx_hash?: string }>({
            tx_hash: '',
        });

        const handleFilter = (e: React.FormEvent) => {
            e.preventDefault();
            router.get('/transactions', {
                type: filterForm.data.type || undefined,
                status: filterForm.data.status || undefined,
                user_id: filterForm.data.user_id || undefined,
            }, {
                preserveState: true,
            });
        };

    const handleApprove = (transactionId: string) => {
        if (!confirm(t('transactions.confirm_approve', { defaultValue: 'Xác nhận duyệt giao dịch này?' }))) {
            return;
        }

        approveForm.post(`/transactions/${transactionId}/approve`, {
            preserveScroll: true,
            onSuccess: () => {
                approveForm.reset();
                router.reload({ only: ['transactions', 'pagination'] });
            },
        });
    };

        const getTransactionIcon = (type?: number | null) => {
            switch (type) {
                case TRANSACTION_TYPE.DEPOSIT:
                    return <ArrowDownCircle className="h-5 w-5 text-green-500" />;
                case TRANSACTION_TYPE.WITHDRAW:
                    return <ArrowUpCircle className="h-5 w-5 text-red-500" />;
                case TRANSACTION_TYPE.REFUND:
                    return <RefreshCcw className="h-5 w-5 text-blue-500" />;
                case TRANSACTION_TYPE.FEE:
                    return <Percent className="h-5 w-5 text-amber-500" />;
                case TRANSACTION_TYPE.CASHBACK:
                    return <Gift className="h-5 w-5 text-emerald-500" />;
                default:
                    return <WalletIcon className="h-5 w-5 text-muted-foreground" />;
            }
        };

        const getStatusColor = (status?: number | null) => {
            switch (status) {
                case TRANSACTION_STATUS.APPROVED:
                case TRANSACTION_STATUS.COMPLETED:
                    return 'bg-green-500 text-white';
                case TRANSACTION_STATUS.PENDING:
                    return 'bg-amber-500 text-white';
                case TRANSACTION_STATUS.REJECTED:
                case TRANSACTION_STATUS.CANCELLED:
                    return 'bg-red-500 text-white';
                default:
                    return 'bg-muted text-foreground';
            }
        };

        const getStatusIcon = (status?: number | null) => {
            switch (status) {
                case TRANSACTION_STATUS.APPROVED:
                case TRANSACTION_STATUS.COMPLETED:
                    return <CheckCircle2 className="h-4 w-4 text-green-500" />;
                case TRANSACTION_STATUS.PENDING:
                    return <Loader2 className="h-4 w-4 text-amber-500 animate-spin" />;
                case TRANSACTION_STATUS.REJECTED:
                case TRANSACTION_STATUS.CANCELLED:
                    return <XCircle className="h-4 w-4 text-red-500" />;
                default:
                    return <Ban className="h-4 w-4 text-muted-foreground" />;
            }
        };

        const getTimeSince = (isoDate?: string | null) => {
            if (!isoDate) return '';
            const date = new Date(isoDate);
            if (Number.isNaN(date.getTime())) return '';

            // Format: "HH:mm - dd/MM/yyyy"
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();

            return `${hours}:${minutes} - ${day}/${month}/${year}`;
        };

        const getExplorerUrl = (network?: string | null, txHash?: string | null) => {
            if (!network || !txHash) return null;

            switch (network.toUpperCase()) {
                case 'BEP20':
                    return `https://bscscan.com/tx/${txHash}`;
                case 'TRC20':
                    return `https://tronscan.org/#/transaction/${txHash}`;
                default:
                    return null;
            }
        };

        const formatUSDT = (amount: number) => {
            return formatter.format(amount) + ' USDT';
        };

        // Map type number sang translation key
        const getTypeKey = (type: number): string => {
            return TRANSACTION_TYPE_MAP[type] || 'unknown';
        };

        // Map status number sang translation key
        const getStatusKey = (status: number): string => {
            return TRANSACTION_STATUS_MAP[status] || 'unknown';
        };

        return (
            <div>
                <Head title={t('transactions.title', { defaultValue: 'Quản lý giao dịch' })} />
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-xl font-semibold">
                        {t('transactions.title', { defaultValue: 'Quản lý giao dịch' })}
                    </h1>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setShowFilters(!showFilters)}
                    >
                        <Filter className="mr-2 h-4 w-4" />
                        {t('common.filter', { defaultValue: 'Lọc' })}
                    </Button>
                </div>

                {showFilters && (
                    <Card className="mb-4">
                        <CardHeader>
                            <CardTitle>{t('common.filter', { defaultValue: 'Lọc' })}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilter} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="filter-type">
                                            {t('transactions.type', { defaultValue: 'Loại giao dịch' })}
                                        </Label>
                                        <select
                                            id="filter-type"
                                            className="border-input bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1"
                                            value={filterForm.data.type}
                                            onChange={(e) => filterForm.setData('type', e.target.value)}
                                        >
                                            <option value="">{t('common.all', { defaultValue: 'Tất cả' })}</option>
                                            <option value="1">{t('wallet.transaction_type.deposit', { defaultValue: 'Nạp tiền' })}</option>
                                            <option value="2">{t('wallet.transaction_type.withdraw', { defaultValue: 'Rút tiền' })}</option>
                                            <option value="3">{t('wallet.transaction_type.refund', { defaultValue: 'Hoàn tiền' })}</option>
                                            <option value="4">{t('wallet.transaction_type.fee', { defaultValue: 'Phí' })}</option>
                                            <option value="5">{t('wallet.transaction_type.cashback', { defaultValue: 'Cashback' })}</option>
                                            <option value="6">{t('wallet.transaction_type.service_purchase', { defaultValue: 'Mua dịch vụ' })}</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="filter-status">
                                            {t('transactions.status', { defaultValue: 'Trạng thái' })}
                                        </Label>
                                        <select
                                            id="filter-status"
                                            className="border-input bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1"
                                            value={filterForm.data.status}
                                            onChange={(e) => filterForm.setData('status', e.target.value)}
                                        >
                                            <option value="">{t('common.all', { defaultValue: 'Tất cả' })}</option>
                                            <option value="1">{t('wallet.transaction_status.pending', { defaultValue: 'Chờ xử lý' })}</option>
                                            <option value="2">{t('wallet.transaction_status.approved', { defaultValue: 'Đã duyệt' })}</option>
                                            <option value="3">{t('wallet.transaction_status.rejected', { defaultValue: 'Từ chối' })}</option>
                                            <option value="4">{t('wallet.transaction_status.completed', { defaultValue: 'Hoàn thành' })}</option>
                                            <option value="5">{t('wallet.transaction_status.cancelled', { defaultValue: 'Đã hủy' })}</option>
                                        </select>
                                    </div>
                                    {canApprove && (
                                        <div className="space-y-2">
                                            <Label htmlFor="filter-user-id">
                                                {t('transactions.user_id', { defaultValue: 'User ID' })}
                                            </Label>
                                            <Input
                                                id="filter-user-id"
                                                type="number"
                                                value={filterForm.data.user_id}
                                                onChange={(e) => filterForm.setData('user_id', e.target.value)}
                                                placeholder={t('transactions.enter_user_id', { defaultValue: 'Nhập User ID' })}
                                            />
                                        </div>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={filterForm.processing}>
                                        <Search className="mr-2 h-4 w-4" />
                                        {t('common.search', { defaultValue: 'Tìm kiếm' })}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            filterForm.reset();
                                            router.get('/transactions');
                                        }}
                                    >
                                        {t('common.reset', { defaultValue: 'Đặt lại' })}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="h-5 w-5" />
                            {t('transactions.list', { defaultValue: 'Danh sách giao dịch' })}
                            <Badge variant="secondary" className="ml-auto">
                                {pagination.total} {t('transactions.items', { defaultValue: 'giao dịch' })}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {transactionsList.length === 0 ? (
                            <div className="py-8 text-center">
                                <WalletIcon className="mx-auto mb-4 h-12 w-12 text-gray-300" />
                                <p className="text-gray-500">
                                    {t('transactions.no_transactions', { defaultValue: 'Không có giao dịch nào' })}
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="space-y-3">
                                    {transactionsList.map((tx) => {
                                        const statusClass = getStatusColor(tx.status);
                                        const explorerUrl = getExplorerUrl(tx.network, tx.txHash);
                                        const isPending = tx.status === TRANSACTION_STATUS.PENDING;

                                        return (
                                            <div
                                                key={tx.id}
                                                className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-50"
                                            >
                                                <div className="flex items-center gap-3 flex-1">
                                                    {getTransactionIcon(tx.type)}
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium">
                                                                {t(`wallet.transaction_type.${getTypeKey(tx.type)}`, { defaultValue: String(tx.type) })}
                                                            </span>
                                                            <Badge className={cn(statusClass, 'text-xs')}>
                                                                {t(`wallet.transaction_status.${getStatusKey(tx.status)}`, { defaultValue: String(tx.status) })}
                                                            </Badge>
                                                            {isPending && canApprove && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => handleApprove(tx.id)}
                                                                    disabled={approveForm.processing}
                                                                >
                                                                    {t('transactions.approve', { defaultValue: 'Duyệt' })}
                                                                </Button>
                                                            )}
                                                        </div>
                                                        {tx.description && (
                                                            <p className="text-sm text-gray-600">{tx.description}</p>
                                                        )}
                                                        {tx.user && (
                                                            <p className="text-xs text-gray-500">
                                                                {tx.user.name} (ID: {tx.user.id})
                                                            </p>
                                                        )}
                                                        <div className="mt-1 flex items-center gap-2 text-xs text-gray-400">
                                                            {getStatusIcon(tx.status)}
                                                            {tx.createdAt && (
                                                                <span>{getTimeSince(tx.createdAt)}</span>
                                                            )}
                                                            {tx.network && (
                                                                <span className="ml-2">• {tx.network}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="text-right">
                                                    <div
                                                        className={cn(
                                                            'font-bold',
                                                            tx.amount > 0 ? 'text-green-600' : 'text-red-600'
                                                        )}
                                                    >
                                                        {tx.amount > 0 ? '+' : ''}
                                                        {formatUSDT(tx.amount)}
                                                    </div>
                                                    {explorerUrl && (
                                                        <a
                                                            href={explorerUrl}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                                                        >
                                                            <ExternalLink className="h-3 w-3" />
                                                            {t('transactions.view_on_explorer', { defaultValue: 'Xem trên explorer' })}
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {pagination.last_page > 1 && (
                                    <div className="mt-4 flex items-center justify-between">
                                        <div className="text-sm text-gray-600">
                                            {t('common.showing', {
                                                defaultValue: 'Hiển thị {{from}} đến {{to}} trong tổng số {{total}}',
                                                from: (pagination.current_page - 1) * pagination.per_page + 1,
                                                to: Math.min(pagination.current_page * pagination.per_page, pagination.total),
                                                total: pagination.total,
                                            })}
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={pagination.current_page === 1}
                                                onClick={() => router.get('/transactions', { page: pagination.current_page - 1, ...filters })}
                                            >
                                                {t('common.previous', { defaultValue: 'Trước' })}
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={pagination.current_page === pagination.last_page}
                                                onClick={() => router.get('/transactions', { page: pagination.current_page + 1, ...filters })}
                                            >
                                                {t('common.next', { defaultValue: 'Sau' })}
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        );
    };

    TransactionsIndex.layout = (page: ReactNode) => (
        <AppLayout breadcrumbs={[{ title: 'transactions.title' }]} children={page} />
    );

    export default TransactionsIndex;


