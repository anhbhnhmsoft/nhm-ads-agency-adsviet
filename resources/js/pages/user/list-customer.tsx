import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { _UserRole, userRolesLabel } from '@/lib/types/constants';
import ListCustomerSearchForm from '@/pages/user/components/list-customer-search-form';
import {
    CustomerListItem,
    CustomerListPagination,
} from '@/pages/user/types/type';
import { user_destroy, user_edit, user_list, user_toggle_disable } from '@/routes';
import { ColumnDef } from '@tanstack/react-table';
import { Check, OctagonX } from 'lucide-react';
import { ReactNode, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useActionCell } from '@/pages/user/hooks/use-action-cell';
import useCheckRole from '@/hooks/use-check-role';
import { router, usePage } from '@inertiajs/react';
import UserInfoDialog from '@/pages/user/components/UserInfoDialog';

type Props = {
    paginator: CustomerListPagination;
};
const ListCustomer = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const [selectedUser, setSelectedUser] = useState<CustomerListItem | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const actionCell = useActionCell<CustomerListItem>({
        canDelete: isAdmin,
        getToggleText: (disabled) => (disabled ? t('common.active') : t('common.disabled')),
        onView: (user) => {
            setSelectedUser(user);
            setIsDialogOpen(true);
        },
        onToggle: (user) => {
            const disabled = !!user.disabled;
            router.post(
                user_toggle_disable({id: user.id}).url,
                { disabled: !disabled },
                { preserveScroll: true}
            );
        },
        onEdit: (user) => {
            router.visit(user_edit({id: user.id }).url);
        },
        onDelete: (user) => {
           router.delete(
            user_destroy({id: user.id}).url,
            { preserveScroll: true }
           )
        },
    });

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
                    return (
                        <div className="flex items-center justify-center">
                            {!disabled ? (
                                <Check className={"size-4 text-green-500"} />
                            ) : (
                                <OctagonX className={"size-4 text-red-500"} />
                            )}
                        </div>
                    );
                },
                meta: {
                    headerClassName: 'text-center',
                }
            },
            {
                id: 'social',
                header: t('common.social_authentication'),
                cell: (cell) => {
                    const row = cell.row.original;
                    if (row.using_telegram && row.using_whatsapp) {
                        return <div className="text-sm">{t('user.authenticated_both', { defaultValue: 'Đã xác thực cả 2' })}</div>;
                    }
                    return (
                        <div className="flex flex-col gap-2">
                            {row.using_telegram && <div className="text-sm">{t('common.using_telegram')}</div>}
                            {row.using_whatsapp && <div className="text-sm">{t('common.using_whatsapp')}</div>}
                            {!row.using_telegram && !row.using_whatsapp && (
                                <div className="text-sm text-gray-400">-</div>
                            )}
                        </div>
                    );
                },
            },
            {
                id:'action',
                header: t('common.action'),
                cell: ({ row }) => actionCell(row.original),
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                }
            }
        ],
        [t, actionCell],
    );

    return (
        <>
            <ListCustomerSearchForm />
            <Separator className={'my-4'} />
            <DataTable columns={columns} paginator={paginator} />
            <UserInfoDialog open={isDialogOpen} onOpenChange={setIsDialogOpen} user={selectedUser} />
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
