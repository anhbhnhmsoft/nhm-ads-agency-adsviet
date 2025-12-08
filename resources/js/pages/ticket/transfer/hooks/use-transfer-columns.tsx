import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import type { Ticket, TicketStatus } from '../../types/type';
import { _TicketStatus } from '../../types/constants';

export const useTransferColumns = () => {
    const { t } = useTranslation();

    const getStatusBadge = (status: TicketStatus) => {
        const statusMap: Record<TicketStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            [_TicketStatus.PENDING]: { label: t('ticket.status.pending'), variant: 'secondary' },
            [_TicketStatus.OPEN]: { label: t('ticket.status.open'), variant: 'default' },
            [_TicketStatus.IN_PROGRESS]: { label: t('ticket.status.in_progress'), variant: 'default' },
            [_TicketStatus.RESOLVED]: { label: t('ticket.status.resolved'), variant: 'outline' },
            [_TicketStatus.CLOSED]: { label: t('ticket.status.closed'), variant: 'secondary' },
        };
        const statusInfo = statusMap[status] || statusMap[_TicketStatus.PENDING];
        return <Badge variant={statusInfo.variant}>{statusInfo.label}</Badge>;
    };

    const columns: ColumnDef<Ticket>[] = useMemo(
        () => [
            {
                accessorKey: 'id',
                header: t('common.id', { defaultValue: 'ID' }),
            },
            {
                accessorKey: 'metadata',
                header: t('ticket.transfer.from_account', { defaultValue: 'Từ tài khoản' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    return metadata?.from_account_name || metadata?.from_account_id || '-';
                },
            },
            {
                id: 'to_account',
                header: t('ticket.transfer.to_account', { defaultValue: 'Đến tài khoản' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    return metadata?.to_account_name || metadata?.to_account_id || '-';
                },
            },
            {
                id: 'amount',
                header: t('ticket.transfer.amount', { defaultValue: 'Số tiền' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const amount = metadata?.amount || 0;
                    const currency = metadata?.currency || 'USD';
                    return `${parseFloat(amount).toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
                },
            },
            {
                accessorKey: 'status',
                header: t('common.status', { defaultValue: 'Trạng thái' }),
                cell: ({ row }) => {
                    const status = row.original.status as TicketStatus;
                    return getStatusBadge(status);
                },
            },
            {
                accessorKey: 'created_at',
                header: t('common.created_at', { defaultValue: 'Ngày tạo' }),
                cell: ({ row }) => {
                    return new Date(row.original.created_at).toLocaleString('vi-VN');
                },
            },
        ],
        [t]
    );

    return { columns };
};

