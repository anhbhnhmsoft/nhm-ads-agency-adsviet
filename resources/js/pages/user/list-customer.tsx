import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import useCheckRole from '@/hooks/use-check-role';
import AppLayout from '@/layouts/app-layout';
import { _UserRole, userRolesLabel } from '@/lib/types/constants';
import ListCustomerSearchForm from '@/pages/user/components/list-customer-search-form';
import UserInfoDialog from '@/pages/user/components/UserInfoDialog';
import { useActionCell } from '@/pages/user/hooks/use-action-cell';
import {
    CustomerListItem,
    CustomerListPagination,
    CustomerListQuery,
    UserOption,
} from '@/pages/user/types/type';
import {
    user_destroy,
    user_edit,
    user_list,
    user_toggle_disable,
    wallet_top_up,
} from '@/routes';
import { router, usePage } from '@inertiajs/react';
import { ColumnDef, ColumnVisibilityState } from '@tanstack/react-table';
import axios from 'axios';
import { ReactNode, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type Props = {
    paginator: CustomerListPagination;
    managers?: UserOption[];
    employees?: UserOption[];
    filters?: CustomerListQuery['filter'];
    canFilterManager?: boolean;
    canFilterEmployee?: boolean;
};

const ListCustomer = ({
    paginator,
    managers = [],
    employees = [],
    filters,
    canFilterManager = false,
    canFilterEmployee = false,
}: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const [selectedUser, setSelectedUser] = useState<CustomerListItem | null>(
        null,
    );
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const managerFilterId = filters?.manager_id ?? null;

    const [columnVisibility, setColumnVisibility] = useState<ColumnVisibilityState>({
        phone: false,
        social: false,
    });
    const [selectedTopUpUser, setSelectedTopUpUser] = useState<CustomerListItem | null>(null);
    const [isTopUpDialogOpen, setIsTopUpDialogOpen] = useState(false);
    const [topUpAmount, setTopUpAmount] = useState('');
    const [processingTopUp, setProcessingTopUp] = useState(false);

    const [selectedDeductUser, setSelectedDeductUser] = useState<CustomerListItem | null>(null);
    const [isDeductDialogOpen, setIsDeductDialogOpen] = useState(false);
    const [deductAmount, setDeductAmount] = useState('');
    const [deductReason, setDeductReason] = useState('');
    const [processingDeduct, setProcessingDeduct] = useState(false);

    const handleTopUpSubmit = () => {
        const amount = Number(topUpAmount);
        if (!selectedTopUpUser || amount <= 0) return;

        setProcessingTopUp(true);
        router.post(
            wallet_top_up({ userId: selectedTopUpUser.id }).url,
            { amount },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsTopUpDialogOpen(false);
                    setTopUpAmount('');
                    setSelectedTopUpUser(null);
                },
                onFinish: () => {
                    setProcessingTopUp(false);
                },
            }
        );
    };

    const handleDeductSubmit = () => {
        const amount = Number(deductAmount);
        if (!selectedDeductUser || amount <= 0) return;

        setProcessingDeduct(true);
        axios.post('/wallets/deduct-balance', {
            user_id: selectedDeductUser.id,
            amount,
            reason: deductReason || undefined,
        }).then(() => {
            setIsDeductDialogOpen(false);
            setDeductAmount('');
            setDeductReason('');
            setSelectedDeductUser(null);
            router.reload({ only: ['paginator'] });
        }).catch((err) => {
            alert(err?.response?.data?.message || 'Lỗi trừ tiền');
        }).finally(() => {
            setProcessingDeduct(false);
        });
    };

    const actionCell = useActionCell<CustomerListItem>({
        canDelete: isAdmin,
        getToggleText: (disabled) =>
            disabled ? t('common.active') : t('common.disabled'),
        onView: (user) => {
            setSelectedUser(user);
            setIsDialogOpen(true);
        },
        onToggle: (user) => {
            const disabled = !!user.disabled;
            router.post(
                user_toggle_disable({ id: user.id }).url,
                { disabled: !disabled },
                { preserveScroll: true },
            );
        },
        onEdit: (user) => {
            router.visit(user_edit({ id: user.id }).url);
        },
        onDelete: (user) => {
            router.delete(user_destroy({ id: user.id }).url, {
                preserveScroll: true,
            });
        },
    });

    const columns: ColumnDef<CustomerListItem>[] = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: t('common.name'),
            },
            {
                accessorKey: 'username',
                header: t('common.username'),
            },
            {
                accessorKey: 'email',
                header: t('common.email'),
                cell: (cell) => {
                    return cell.row.original.email || '-';
                },
            },
            {
                accessorKey: 'telegram_id',
                header: t('common.telegram_id'),
                cell: (cell) => {
                    return cell.row.original.telegram_id || '-';
                },
            },
            {
                id: 'managed_by',
                header: t('user.manager_owner', {
                    defaultValue: 'Thuộc quản lý',
                }),
                cell: ({ row }) => {
                    const owner = row.original.owner;
                    const manager = row.original.manager;

                    // Nếu có filter manager và owner là EMPLOYEE, hiển thị cả employee và manager
                    if (
                        managerFilterId &&
                        owner?.role === _UserRole.EMPLOYEE &&
                        manager?.username
                    ) {
                        return t('user.manager_relation', {
                            employee: owner.username,
                            manager: manager.username,
                        });
                    }

                    // Ưu tiên hiển thị owner (người trực tiếp giới thiệu) nếu có
                    if (owner?.username) {
                        return owner.username;
                    }

                    // Nếu không có owner, hiển thị manager nếu có
                    if (manager?.username) {
                        return manager.username;
                    }

                    return '-';
                },
                meta: {
                    cellClassName: 'min-w-[180px]',
                },
            },
            {
                accessorKey: 'phone',
                header: t('common.phone'),
                cell: (cell) => {
                    return cell.row.original.phone || '-';
                },
            },
            {
                accessorKey: 'wallet_balance',
                header: t('wallet.balance', { defaultValue: 'Số dư tiền balance' }),
                cell: (cell) => {
                    const balance = cell.row.original.wallet_balance;
                    if (balance === undefined || balance === null) return '0.00 USDT';
                    return Number(balance).toLocaleString('vi-VN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }) + ' USDT';
                },
            },
            {
                accessorKey: 'referral_code',
                header: t('common.referral_code'),
            },
            {
                accessorKey: 'role',
                header: t('common.role'),
                cell: (cell) => {
                    return t(userRolesLabel[cell.row.original.role]);
                },
            },
            {
                id: 'social',
                header: t('common.social_authentication'),
                cell: (cell) => {
                    const row = cell.row.original;
                    const hasEmail = !!row.email_verified_at;
                    const hasTelegram = !!row.using_telegram;

                    if (hasEmail && hasTelegram) {
                        return (
                            <div className="text-sm">
                                {t('user.authenticated_both', {
                                    defaultValue: 'Đã xác thực cả 2',
                                })}
                            </div>
                        );
                    }
                    return (
                        <div className="flex flex-col gap-2">
                            {hasEmail && (
                                <div className="text-sm">
                                    {t('common.using_email')}
                                </div>
                            )}
                            {hasTelegram && (
                                <div className="text-sm">
                                    {t('common.using_telegram')}
                                </div>
                            )}
                            {!hasEmail && !hasTelegram && (
                                <div className="text-sm text-gray-400">-</div>
                            )}
                        </div>
                    );
                },
            },
            {
                id: 'manual_topup',
                header: t('wallet.top_up', { defaultValue: 'Nạp tiền thủ công' }),
                cell: ({ row }) => {
                    return (
                        <div className="flex gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setSelectedTopUpUser(row.original);
                                    setTopUpAmount('');
                                    setIsTopUpDialogOpen(true);
                                }}
                            >
                                Nạp tiền
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-red-600 border-red-300 hover:bg-red-50"
                                onClick={() => {
                                    setSelectedDeductUser(row.original);
                                    setDeductAmount('');
                                    setDeductReason('');
                                    setIsDeductDialogOpen(true);
                                }}
                            >
                                Trừ tiền
                            </Button>
                        </div>
                    );
                },
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
            },
            {
                id: 'action',
                header: t('common.action'),
                cell: ({ row }) => actionCell(row.original),
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
            },
        ],
        [t, actionCell, managerFilterId, setSelectedTopUpUser, setTopUpAmount, setIsTopUpDialogOpen],
    );

    return (
        <>
            <ListCustomerSearchForm
                managers={managers}
                employees={employees}
                initialFilter={filters}
                showManagerSelect={canFilterManager}
                showEmployeeSelect={canFilterEmployee}
            />
            <Separator className={'my-4'} />
            <div className="flex justify-between items-center mb-4 gap-2">
                <h3 className="text-lg font-medium text-gray-900">
                    {t('user.customer_list_title', { defaultValue: 'Danh sách khách hàng' })}
                </h3>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="sm">
                            {t('common.columns', { defaultValue: 'Hiển thị cột' })}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-[200px]">
                        <DropdownMenuCheckboxItem
                            checked={columnVisibility['phone'] !== false}
                            onCheckedChange={(value) =>
                                setColumnVisibility((prev) => ({
                                    ...prev,
                                    phone: value,
                                }))
                            }
                        >
                            {t('common.phone')}
                        </DropdownMenuCheckboxItem>
                        <DropdownMenuCheckboxItem
                            checked={columnVisibility['social'] !== false}
                            onCheckedChange={(value) =>
                                setColumnVisibility((prev) => ({
                                    ...prev,
                                    social: value,
                                }))
                            }
                        >
                            {t('common.social_authentication')}
                        </DropdownMenuCheckboxItem>
                        <DropdownMenuCheckboxItem
                            checked={columnVisibility['wallet_balance'] !== false}
                            onCheckedChange={(value) =>
                                setColumnVisibility((prev) => ({
                                    ...prev,
                                    wallet_balance: value,
                                }))
                            }
                        >
                            {t('wallet.balance', { defaultValue: 'Số dư tiền balance' })}
                        </DropdownMenuCheckboxItem>
                        <DropdownMenuCheckboxItem
                            checked={columnVisibility['manual_topup'] !== false}
                            onCheckedChange={(value) =>
                                setColumnVisibility((prev) => ({
                                    ...prev,
                                    manual_topup: value,
                                }))
                            }
                        >
                            {t('wallet.top_up', { defaultValue: 'Nạp tiền thủ công' })}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <DataTable 
                columns={columns} 
                paginator={paginator} 
                columnVisibility={columnVisibility}
                onColumnVisibilityChange={setColumnVisibility}
            />
            <UserInfoDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                user={selectedUser}
            />
            <Dialog open={isTopUpDialogOpen} onOpenChange={setIsTopUpDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {t('user.manual_top_up_title', {
                                defaultValue: 'Nạp tiền thủ công cho khách hàng',
                            })}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-500">
                                {t('common.customer', { defaultValue: 'Khách hàng' })}
                            </label>
                            <div className="font-semibold text-sm">
                                {selectedTopUpUser?.name} ({selectedTopUpUser?.username})
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-500">
                                {t('wallet.balance', { defaultValue: 'Số dư hiện tại' })}
                            </label>
                            <div className="text-sm font-medium text-gray-900">
                                {selectedTopUpUser?.wallet_balance?.toLocaleString('vi-VN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }) ?? '0.00'}{' '}
                                USDT
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label htmlFor="top_up_amount" className="text-sm font-medium text-gray-500">
                                {t('wallet.amount', { defaultValue: 'Số tiền nạp (USDT)' })}
                            </label>
                            <Input
                                id="top_up_amount"
                                type="number"
                                min="0"
                                step="any"
                                value={topUpAmount}
                                onChange={(e) => setTopUpAmount(e.target.value)}
                                placeholder="Nhập số tiền..."
                            />
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setIsTopUpDialogOpen(false)}
                        >
                            {t('common.cancel', { defaultValue: 'Hủy' })}
                        </Button>
                        <Button
                            onClick={handleTopUpSubmit}
                            disabled={!topUpAmount || Number(topUpAmount) <= 0 || processingTopUp}
                        >
                            {processingTopUp ? t('common.processing', { defaultValue: 'Đang xử lý...' }) : t('wallet.top_up', { defaultValue: 'Nạp tiền' })}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={isDeductDialogOpen} onOpenChange={setIsDeductDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            Trừ tiền từ ví khách hàng
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-500">
                                Khách hàng
                            </label>
                            <div className="font-semibold text-sm">
                                {selectedDeductUser?.name} ({selectedDeductUser?.username})
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-500">
                                Số dư hiện tại
                            </label>
                            <div className="text-sm font-medium text-gray-900">
                                {selectedDeductUser?.wallet_balance?.toLocaleString('vi-VN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }) ?? '0.00'}{' '}
                                USDT
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label htmlFor="deduct_amount" className="text-sm font-medium text-gray-500">
                                Số tiền trừ (USDT) *
                            </label>
                            <Input
                                id="deduct_amount"
                                type="number"
                                min="0"
                                step="any"
                                value={deductAmount}
                                onChange={(e) => setDeductAmount(e.target.value)}
                                placeholder="Nhập số tiền muốn trừ..."
                            />
                        </div>
                        <div className="space-y-2">
                            <label htmlFor="deduct_reason" className="text-sm font-medium text-gray-500">
                                Lý do
                            </label>
                            <Input
                                id="deduct_reason"
                                value={deductReason}
                                onChange={(e) => setDeductReason(e.target.value)}
                                placeholder="Nhập lý do trừ tiền..."
                            />
                        </div>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setIsDeductDialogOpen(false)}
                        >
                            Hủy
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeductSubmit}
                            disabled={!deductAmount || Number(deductAmount) <= 0 || processingDeduct}
                        >
                            {processingDeduct ? 'Đang xử lý...' : 'Trừ tiền'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
};

ListCustomer.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[
            {
                title: 'menu.user_list_customer',
                href: user_list().url,
            },
        ]}
        children={page}
    />
);

export default ListCustomer;
