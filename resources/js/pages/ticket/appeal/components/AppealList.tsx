import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable } from '@/components/table/data-table';
import { useAppealColumns } from '../hooks/use-appeal-columns';
import type { AppealPageProps } from '../types/type';
import type { Ticket } from '../../types/type';
import { ticket_show } from '@/routes';

type AppealListProps = {
    tickets: AppealPageProps['tickets'];
};

export const AppealList = ({ tickets }: AppealListProps) => {
    const { t } = useTranslation();
    const { columns } = useAppealColumns();

    const handleRowClick = (row: Ticket) => {
        router.visit(ticket_show({ id: row.id }).url);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.appeal.in_progress', { defaultValue: 'Đang thực hiện' })}</CardTitle>
            </CardHeader>
            <CardContent>
                {tickets && tickets.data.length > 0 ? (
                    <DataTable columns={columns} paginator={tickets} onRowClick={handleRowClick} />
                ) : (
                    <div className="text-center py-8 text-muted-foreground">
                        {t('ticket.appeal.no_requests', { defaultValue: 'Chưa có yêu cầu nào' })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

