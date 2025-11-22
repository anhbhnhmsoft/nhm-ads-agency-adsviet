import React, { ReactNode, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { router, useForm, Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Clock, Filter, Search } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { TransactionList } from '@/components/transactions/transaction-list';
import type { TransactionsIndexProps } from './types/type';

const TransactionsIndex = ({ transactions, pagination, filters, canApprove }: TransactionsIndexProps) => {
    const { t } = useTranslation();
    const [showFilters, setShowFilters] = useState(false);
    const [showWithdrawInfo, setShowWithdrawInfo] = useState(false);
    const [selectedWithdrawInfo, setSelectedWithdrawInfo] = useState<{
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
    } | null>(null);
    const [approveLoadingId, setApproveLoadingId] = useState<string | null>(null);
    const [cancelLoadingId, setCancelLoadingId] = useState<string | null>(null);

    const transactionsList = transactions?.data || [];

    const filterForm = useForm({
        type: filters.type || '',
        status: filters.status || '',
        user_id: filters.user_id || '',
    });

    const approveForm = useForm<{ tx_hash?: string }>({
        tx_hash: '',
    });

    const cancelForm = useForm({});

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/transactions',
            {
                type: filterForm.data.type || undefined,
                status: filterForm.data.status || undefined,
                user_id: filterForm.data.user_id || undefined,
            },
            {
                preserveState: true,
            }
        );
    };

    const handleApprove = (transactionId: string) => {
        if (!confirm(t('transactions.confirm_approve', { defaultValue: 'Xác nhận duyệt giao dịch này?' }))) {
            return;
        }

        setApproveLoadingId(transactionId);
        approveForm.post(`/transactions/${transactionId}/approve`, {
            preserveScroll: true,
            onSuccess: () => {
                approveForm.reset();
                router.reload({ only: ['transactions', 'pagination'] });
            },
            onFinish: () => {
                setApproveLoadingId((current) => (current === transactionId ? null : current));
            },
        });
    };

    const handleCancel = (transactionId: string) => {
        if (!confirm(t('transactions.confirm_cancel', { defaultValue: 'Xác nhận hủy giao dịch này?' }))) {
            return;
        }

        setCancelLoadingId(transactionId);
        cancelForm.post(`/transactions/${transactionId}/cancel`, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['transactions', 'pagination'] });
            },
            onFinish: () => {
                setCancelLoadingId((current) => (current === transactionId ? null : current));
            },
        });
    };

    const handleViewWithdrawInfo = (
        withdrawInfo?: { bank_name?: string; account_holder?: string; account_number?: string } | null
    ) => {
        setSelectedWithdrawInfo(withdrawInfo ?? null);
        setShowWithdrawInfo(true);
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
                        <TransactionList
                            transactions={transactionsList}
                            canApprove={canApprove}
                            onApprove={handleApprove}
                            onCancel={handleCancel}
                            approveLoadingId={approveLoadingId}
                            cancelLoadingId={cancelLoadingId}
                            onViewWithdrawInfo={handleViewWithdrawInfo}
                        />

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
                    </CardContent>
                </Card>

                {/* Dialog hiển thị thông tin rút tiền */}
                <Dialog open={showWithdrawInfo} onOpenChange={setShowWithdrawInfo}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t('transactions.withdraw_info_title', { defaultValue: 'Thông tin rút tiền' })}</DialogTitle>
                            <DialogDescription>
                                {t('transactions.withdraw_info_description', { defaultValue: 'Thông tin tài khoản người dùng đã nhập' })}
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
            </div>
        );
    };

    TransactionsIndex.layout = (page: ReactNode) => (
        <AppLayout breadcrumbs={[{ title: 'transactions.title' }]} children={page} />
    );

    export default TransactionsIndex;


