import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import type { Ticket, TicketStatus } from '../../types/type';
import { _TicketStatus } from '../../types/constants';
import { _PlatformType } from '@/lib/types/constants';

export const useShareColumns = () => {
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

    const getPlatformName = (platform: number) => {
        if (platform === _PlatformType.GOOGLE) {
            return t('enum.platform_type.google', { defaultValue: 'Google Ads' });
        }
        if (platform === _PlatformType.META) {
            return t('enum.platform_type.meta', { defaultValue: 'Meta Ads' });
        }
        return '-';
    };

    const columns: ColumnDef<Ticket>[] = useMemo(
        () => [
            {
                accessorKey: 'id',
                header: t('common.id', { defaultValue: 'ID' }),
            },
            {
                id: 'platform',
                header: t('ticket.share.platform', { defaultValue: 'Kênh quảng cáo' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const platform = metadata?.platform;
                    return platform ? getPlatformName(platform) : '-';
                },
            },
            {
                id: 'account',
                header: t('ticket.share.account', { defaultValue: 'Tài khoản' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const accountName = metadata?.account_name;
                    const accountId = metadata?.account_id;
                    if (accountName && accountId) {
                        return `${accountName} (${accountId})`;
                    }
                    return accountName || accountId || '-';
                },
            },
            {
                id: 'bm_bc_mcc_id',
                header: t('ticket.share.bm_bc_mcc_id', { defaultValue: 'ID BM/MCC' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    return metadata?.bm_bc_mcc_id || '-';
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

