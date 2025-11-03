import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { userRolesLabel } from '@/lib/types/constants';
import ListCustomerSearchForm from '@/pages/user/components/list-customer-search-form';
import {
    CustomerListItem,
    CustomerListPagination,
} from '@/pages/user/types/type';
import { user_list } from '@/routes';
import { ColumnDef } from '@tanstack/react-table';
import { Check, OctagonX, MoreHorizontal, BookUser } from 'lucide-react';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

type Props = {
    paginator: CustomerListPagination;
};
const ListCustomer = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const columns: ColumnDef<CustomerListItem>[] = useMemo(
        () => [
            {
                accessorKey: 'id',
                header: t('common.id'),
            },
            {
                accessorKey: 'name',
                header: t('common.name'),
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
                        return <Check className={'size-4 text-green-500'} />;
                    } else {
                        return <OctagonX className={'size-4 text-red-500'} />;
                    }
                },
            },
            {
                id: 'social',
                header: t('common.social_authentication'),
                cell: (cell) => {
                    const row = cell.row.original;
                    return (
                        <div className="flex flex-col gap-2">
                            {row.using_telegram && t('common.using_telegram')}
                            {row.using_whatsapp && t('common.using_whatsapp')}
                        </div>
                    );
                },
            },
            {
                id:'action',
                header: t('common.action'),
                cell: ({ row }) => {
                    const user = row.original;
                    return (
                        <DropdownMenu>
                            <DropdownMenuTrigger>
                                <MoreHorizontal className={'size-4'} />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem>
                                    <BookUser />
                                    Thông tin
                                </DropdownMenuItem>
                                <DropdownMenuItem>
                                    {t('common.delete')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                }
            }
        ],
        [t],
    );

    return (
        <>
            <ListCustomerSearchForm />
            <Separator className={'my-4'} />
            <DataTable columns={columns} paginator={paginator} />
        </>
    );
};

ListCustomer.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[
            {
                title: 'menu.user_list_customer',
                href: user_list().url,
            },
        ]}
        children={page}
    />
);

export default ListCustomer;
