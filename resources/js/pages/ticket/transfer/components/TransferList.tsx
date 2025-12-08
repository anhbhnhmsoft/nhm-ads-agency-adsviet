import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable } from '@/components/table/data-table';
import { useTransferColumns } from '../hooks/use-transfer-columns';
import type { TransferPageProps } from '../../types/type';
import type { Ticket } from '../../types/type';
import { ticket_show } from '@/routes';

type TransferListProps = {
    tickets: TransferPageProps['tickets'];
};

export const TransferList = ({ tickets }: TransferListProps) => {
    const { t } = useTranslation();
    const { columns } = useTransferColumns();

    const handleRowClick = (row: Ticket) => {
        router.visit(ticket_show({ id: row.id }).url);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.transfer.in_progress', { defaultValue: 'Đang thực hiện' })}</CardTitle>
            </CardHeader>
            <CardContent>
                {tickets && tickets.data.length > 0 ? (
                    <DataTable columns={columns} paginator={tickets} onRowClick={handleRowClick} />
                ) : (
                    <div className="text-center py-8 text-muted-foreground">
                        {t('ticket.transfer.no_requests', { defaultValue: 'Chưa có yêu cầu nào' })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

