import { useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import { Badge } from '@/components/ui/badge';
import { CommissionReportPagination, CommissionTransactionItem } from '@/pages/commission/types/type';

type Props = {
    paginator: CommissionReportPagination;
};

const Report = ({ paginator }: Props) => {
    const { t } = useTranslation();

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'service':
                return t('commission.type_service', { defaultValue: 'Hoa hồng dịch vụ' });
            case 'spending':
                return t('commission.type_spending', { defaultValue: 'Hoa hồng theo spending' });
            default:
                return type;
        }
    };

    const columns: ColumnDef<CommissionTransactionItem>[] = useMemo(
        () => [
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
                accessorKey: 'period',
                header: t('commission.period', { defaultValue: 'Kỳ (YYYY-MM)' }),
                cell: ({ row }) => row.original.period || '-',
            },
            {
                accessorKey: 'base_amount',
                header: t('commission.base_amount', { defaultValue: 'Số tiền gốc (USD)' }),
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
        ],
        [t],
    );

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {t('commission.report_title', { defaultValue: 'Báo cáo hoa hồng' })}
                    </h1>
                </div>

                <DataTable columns={columns} paginator={paginator} />
            </div>
        </AppLayout>
    );
};

export default Report;



