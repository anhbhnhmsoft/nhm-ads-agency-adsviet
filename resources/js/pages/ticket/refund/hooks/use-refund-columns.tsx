import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import type { Ticket, TicketStatus } from '../../types/type';
import { _TicketStatus } from '../../types/constants';
import { _PlatformType } from '@/lib/types/constants';

export const useRefundColumns = () => {
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
                header: t('ticket.refund.platform', { defaultValue: 'Kênh quảng cáo' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const platform = metadata?.platform;
                    return platform ? getPlatformName(platform) : '-';
                },
            },
            {
                id: 'accounts',
                header: t('ticket.refund.accounts', { defaultValue: 'Tài khoản thanh lý' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const accountIds = metadata?.account_ids || [];
                    const accountNames = metadata?.account_names || [];
                    
                    if (Array.isArray(accountIds) && accountIds.length > 0) {
                        return accountIds.map((accountId: string, index: number) => {
                            const accountName = accountNames[index] || '';
                            return accountName ? `${accountName} (${accountId})` : accountId;
                        }).join(', ');
                    }
                    return '-';
                },
            },
            {
                id: 'liquidation_type',
                header: t('ticket.refund.liquidation_type', { defaultValue: 'Loại thanh lý' }),
                cell: ({ row }) => {
                    const metadata = row.original.metadata as any;
                    const liquidationType = metadata?.liquidation_type;
                    if (liquidationType === 'withdraw_to_wallet') {
                        return t('ticket.refund.withdraw_to_wallet', { defaultValue: 'Rút Tiền Về Ví' });
                    }
                    return liquidationType || '-';
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

