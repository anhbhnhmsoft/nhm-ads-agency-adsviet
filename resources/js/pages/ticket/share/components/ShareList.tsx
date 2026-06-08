import { DataTable } from '@/components/table/data-table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ticket_show } from '@/routes';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { Ticket } from '../../types/type';
import { useShareColumns } from '../hooks/use-share-columns';
import type { SharePageProps } from '../types/type';

type ShareListProps = {
    tickets: SharePageProps['tickets'];
};

export const ShareList = ({ tickets }: ShareListProps) => {
    const { t } = useTranslation();
    const { columns } = useShareColumns();

    const handleRowClick = (row: Ticket) => {
        router.visit(ticket_show({ id: row.id }).url);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {t('ticket.share.in_progress', {
                        defaultValue: 'Đang thực hiện',
                    })}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {tickets && tickets.data.length > 0 ? (
                    <DataTable
                        columns={columns}
                        paginator={tickets}
                        onRowClick={handleRowClick}
                    />
                ) : (
                    <div className="py-8 text-center text-muted-foreground">
                        {t('ticket.share.no_requests', {
                            defaultValue: 'Chưa có yêu cầu nào',
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};
