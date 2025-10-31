import AppLayout from '@/layouts/app-layout';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { EmployeeListItem, EmployeeListPagination } from '@/pages/user/types/type';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import { userRolesLabel } from '@/lib/types/constants';
import { Check, OctagonX } from 'lucide-react';
import ListEmployeeSearchForm from '@/pages/user/components/list-employee-search-form';
import { Separator } from '@/components/ui/separator';
import { user_list_employee } from '@/routes';

type Props = {
    paginator: EmployeeListPagination;
}
const ListEmployee = ({paginator}: Props) => {
    const {t} = useTranslation();
    const columns: ColumnDef<EmployeeListItem>[] = useMemo(() => [
        {
            accessorKey: 'id',
            header: t('common.id'),
        },
        {
            accessorKey: 'username',
            header: t('common.username'),
        },
        {
            accessorKey: 'phone',
            header: t('common.phone'),
            cell: (cell) => {
                return cell.row.original.phone || '-';
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
            accessorKey: 'disabled',
            header: t('common.account_active'),
            cell: (cell) => {
                const disabled = cell.row.original.disabled;
                if (!disabled) {
                    return <Check className={"size-4 text-green-500"} />;
                }else{
                    return <OctagonX className={"size-4 text-red-500"} />
                }
            }
        },
    ], [t]);
    return (
        <div>
            <ListEmployeeSearchForm />
            <Separator className={'my-4'} />
            <DataTable columns={columns} paginator={paginator} />
        </div>
    );
};

ListEmployee.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[
        {
            title: 'menu.user_list_employee',
            href: user_list_employee().url,
        },

    ]} children={page} />
);
export default ListEmployee;
