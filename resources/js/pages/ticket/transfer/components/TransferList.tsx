import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable } from '@/components/table/data-table';
import { useTransferColumns } from '../hooks/use-transfer-columns';
import type { TransferPageProps } from '../../types/type';

type TransferListProps = {
    tickets: TransferPageProps['tickets'];
};

export const TransferList = ({ tickets }: TransferListProps) => {
    const { t } = useTranslation();
    const { columns } = useTransferColumns();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.transfer.in_progress', { defaultValue: 'Đang thực hiện' })}</CardTitle>
            </CardHeader>
            <CardContent>
                {tickets && tickets.data.length > 0 ? (
                    <DataTable columns={columns} paginator={tickets} />
                ) : (
                    <div className="text-center py-8 text-muted-foreground">
                        {t('ticket.transfer.no_requests', { defaultValue: 'Chưa có yêu cầu nào' })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

