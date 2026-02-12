import { useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { ColumnDef, RowSelectionState } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { CommissionReportPagination, CommissionTransactionItem, CommissionSummaryItem } from '@/pages/commission/types/type';
import { commissions_report_index, commissions_report_mark_paid } from '@/routes';
import { router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import CommissionReportSearchForm from '@/pages/commission/components/search-form';

type Props = {
    paginator: CommissionReportPagination;
    summary_by_employee: CommissionSummaryItem[];
};

const Report = ({ paginator, summary_by_employee }: Props) => {
    const { t } = useTranslation();
    const { url } = usePage();
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});

    // Kiểm tra xem có filter trong URL không (VD: bấm "Lịch sử" hoặc tìm kiếm)
    const hasFilterInUrl = url.includes('filter%5B') || url.includes('filter[');
    const hasEmployeeFilter = url.includes('employee_id');

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'service':
                return t('commission.type_service', { defaultValue: 'Hoa hồng dịch vụ' });
            case 'spending':
                return t('commission.type_spending', { defaultValue: 'Hoa hồng theo spending' });
            case 'account':
                return t('commission.type_account', { defaultValue: 'Hoa hồng bán account' });
            default:
                return type;
        }
    };

    const formatDateTime = (dateString?: string) => {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        } catch {
            return dateString;
        }
    };

    const handleBulkMarkAsPaid = () => {
        const selectedIds = Object.keys(rowSelection).map((key) => {
            const index = parseInt(key, 10);
            return paginator.data[index]?.id;
        }).filter(Boolean) as string[];

        if (selectedIds.length === 0) {
            return;
        }

        router.post(
            commissions_report_mark_paid().url,
            {
                ids: selectedIds,
                paid_at: new Date().toISOString().slice(0, 10),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRowSelection({});
                },
            },
        );
    };

    const columns: ColumnDef<CommissionTransactionItem>[] = useMemo(
        () => [
            {
                id: 'select',
                header: ({ table }) => (
                    <Checkbox
                        checked={table.getIsAllPageRowsSelected()}
                        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                        aria-label="Select all"
                    />
                ),
                cell: ({ row }) => {
                    if (row.original.is_paid) {
                        return null;
                    }
                    return (
                        <Checkbox
                            checked={row.getIsSelected()}
                            onCheckedChange={(value) => row.toggleSelected(!!value)}
                            aria-label="Select row"
                        />
                    );
                },
                enableSorting: false,
                enableHiding: false,
            },
            {
                accessorKey: 'employee.name',
                header: t('commission.employee', { defaultValue: 'Nhân viên/Quản lý' }),
                cell: ({ row }) => {
                    const employee = row.original.employee;
                    return employee ? `${employee.name} (${employee.username})` : '-';
                },
            },
            {
                accessorKey: 'customer.name',
                header: t('commission.customer', { defaultValue: 'Khách hàng' }),
                cell: ({ row }) => {
                    const customer = row.original.customer;
                    return customer ? `${customer.name} (${customer.username})` : '-';
                },
            },
            {
                accessorKey: 'type',
                header: t('commission.type', { defaultValue: 'Loại hoa hồng' }),
                cell: ({ row }) => (
                    <Badge variant="outline">
                        {getTypeLabel(row.original.type)}
                    </Badge>
                ),
            },
            {
                accessorKey: 'created_at',
                header: t('commission.period', { defaultValue: 'Kỳ' }),
                cell: ({ row }) => {
                    // Ưu tiên hiển thị created_at nếu có, nếu không thì dùng period
                    const dateStr = row.original.created_at || row.original.period;
                    return formatDateTime(dateStr);
                },
            },
            {
                accessorKey: 'base_amount',
                header: t('commission.revenue', { defaultValue: 'Doanh thu (USD)' }),
                cell: ({ row }) => {
                    const raw = row.original.base_amount;
                    const num = Number(raw);
                    return Number.isFinite(num) ? num.toLocaleString('vi-VN') + ' USD' : raw;
                },
            },
            {
                accessorKey: 'commission_rate',
                header: t('commission.rate', { defaultValue: 'Tỷ lệ (%)' }),
                cell: ({ row }) => {
                    const raw = row.original.commission_rate;
                    const num = Number(raw);
                    return Number.isFinite(num) ? `${num.toFixed(2)}%` : raw;
                },
            },
            {
                accessorKey: 'commission_amount',
                header: t('commission.commission_amount', { defaultValue: 'Tiền hoa hồng (USD)' }),
                cell: ({ row }) => {
                    const raw = row.original.commission_amount;
                    const num = Number(raw);
                    return Number.isFinite(num) ? num.toLocaleString('vi-VN') + ' USD' : raw;
                },
            },
            {
                accessorKey: 'is_paid',
                header: t('commission.paid_status', { defaultValue: 'Trạng thái thanh toán' }),
                cell: ({ row }) => {
                    const isPaid = row.original.is_paid;
                    return (
                        <Badge variant={isPaid ? 'default' : 'outline'}>
                            {isPaid
                                ? t('commission.paid', { defaultValue: 'Đã thanh toán' })
                                : t('commission.unpaid', { defaultValue: 'Chưa thanh toán' })}
                        </Badge>
                    );
                },
            },
            {
                id: 'actions',
                header: t('common.actions', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    const isPaid = row.original.is_paid;
                    if (isPaid) {
                        return null;
                    }

                    return (
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                >
                                    {t('commission.mark_as_paid', { defaultValue: 'Xác nhận thanh toán' })}
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        {t('commission.confirm_mark_paid_title', {
                                            defaultValue: 'Xác nhận đã thanh toán hoa hồng',
                                        })}
                                    </DialogTitle>
                                    <DialogDescription>
                                        {t('commission.confirm_mark_paid_description', {
                                            defaultValue:
                                                'Bạn có chắc chắn muốn đánh dấu giao dịch hoa hồng này là đã thanh toán? Hành động này có thể ảnh hưởng tới báo cáo lợi nhuận.',
                                        })}
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button type="button" variant="outline">
                                            {t('common.cancel', { defaultValue: 'Hủy' })}
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="button"
                                        onClick={() => {
                                            router.post(
                                                commissions_report_mark_paid().url,
                                                {
                                                    ids: [row.original.id],
                                                    paid_at: new Date().toISOString().slice(0, 10),
                                                },
                                                {
                                                    preserveScroll: true,
                                                },
                                            );
                                        }}
                                    >
                                        {t('commission.confirm', { defaultValue: 'Xác nhận' })}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    );
                },
            },
        ],
        [t],
    );

    const selectedCount = Object.keys(rowSelection).length;
    const hasUnpaidSelected = Object.keys(rowSelection).some((key) => {
        const index = parseInt(key, 10);
        return !paginator.data[index]?.is_paid;
    });

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        {hasFilterInUrl && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    router.get(commissions_report_index().url);
                                }}
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back', { defaultValue: 'Quay lại' })}
                            </Button>
                        )}
                        <h1 className="text-2xl font-semibold">
                            {t('commission.report_title', { defaultValue: 'Báo cáo hoa hồng' })}
                        </h1>
                    </div>
                </div>

                {/* Component tìm kiếm */}
                {!hasFilterInUrl && <CommissionReportSearchForm />}

                {/* Bảng tổng hợp theo nhân viên - chỉ hiển thị khi KHÔNG có filter */}
                {!hasFilterInUrl && summary_by_employee && summary_by_employee.length > 0 && (
                    <>
                        <Separator className="my-4" />
                        <div className="rounded-md border bg-white p-4 space-y-2">
                            <h2 className="text-sm font-semibold">
                                {t('commission.summary_by_employee', { defaultValue: 'Tổng hợp theo nhân viên/Quản lý' })}
                            </h2>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left py-2 pr-4">
                                            {t('commission.employee', { defaultValue: 'Nhân viên/Quản lý' })}
                                        </th>
                                        <th className="text-right py-2 pr-4">
                                            {t('commission.revenue', { defaultValue: 'Doanh thu (USD)' })}
                                        </th>
                                        <th className="text-right py-2 pr-4">
                                            {t('commission.commission_amount', { defaultValue: 'Tiền hoa hồng (USD)' })}
                                        </th>
                                        <th className="text-right py-2">
                                            {t('common.actions', { defaultValue: 'Hành động' })}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {summary_by_employee.map((item) => {
                                        const employee = item.employee;
                                        const base = Number(item.total_base_amount);
                                        const commission = Number(item.total_commission_amount);
                                        return (
                                            <tr key={item.employee_id} className="border-b last:border-0">
                                                <td className="py-1 pr-4">
                                                    {employee
                                                        ? `${employee.name} (${employee.username})`
                                                        : item.employee_id}
                                                </td>
                                                <td className="py-1 pr-4 text-right">
                                                    {Number.isFinite(base)
                                                        ? base.toLocaleString('vi-VN') + ' USD'
                                                        : item.total_base_amount}
                                                </td>
                                                <td className="py-1 pr-4 text-right">
                                                    {Number.isFinite(commission)
                                                        ? commission.toLocaleString('vi-VN') + ' USD'
                                                        : item.total_commission_amount}
                                                </td>
                                                <td className="py-1 text-right">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            router.get(
                                                                commissions_report_index().url,
                                                                {
                                                                    filter: {
                                                                        employee_id: item.employee_id,
                                                                    },
                                                                },
                                                            );
                                                        }}
                                                    >
                                                        {t('commission.view_history', { defaultValue: 'Lịch sử' })}
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                {/* Bảng chi tiết hoa hồng - chỉ hiển thị khi có filter */}
                {hasFilterInUrl && (
                    <>
                        <Separator className="my-4" />
                        {selectedCount > 0 && hasUnpaidSelected && (
                            <div className="flex items-center justify-between rounded-md border bg-white p-4">
                                <span className="text-sm">
                                    {t('commission.selected_count', {
                                        defaultValue: 'Đã chọn {{count}} mục',
                                        count: selectedCount,
                                    })}
                                </span>
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button type="button" variant="default" size="sm">
                                            {t('commission.bulk_mark_as_paid', {
                                                defaultValue: 'Xác nhận thanh toán hàng loạt',
                                            })}
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>
                                                {t('commission.confirm_bulk_mark_paid_title', {
                                                    defaultValue: 'Xác nhận thanh toán hàng loạt',
                                                })}
                                            </DialogTitle>
                                            <DialogDescription>
                                                {t('commission.confirm_bulk_mark_paid_description', {
                                                    defaultValue:
                                                        `Bạn có chắc chắn muốn đánh dấu ${selectedCount} giao dịch hoa hồng là đã thanh toán? Hành động này có thể ảnh hưởng tới báo cáo lợi nhuận.`,
                                                })}
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button type="button" variant="outline">
                                                    {t('common.cancel', { defaultValue: 'Hủy' })}
                                                </Button>
                                            </DialogClose>
                                            <Button type="button" onClick={handleBulkMarkAsPaid}>
                                                {t('commission.confirm', { defaultValue: 'Xác nhận' })}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        )}
                        <DataTable 
                            columns={columns} 
                            paginator={paginator}
                            rowSelection={rowSelection}
                            onRowSelectionChange={setRowSelection}
                        />
                    </>
                )}
            </div>
        </AppLayout>
    );
};

export default Report;
