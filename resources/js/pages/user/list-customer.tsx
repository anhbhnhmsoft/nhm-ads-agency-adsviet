import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { _UserRole, userRolesLabel } from '@/lib/types/constants';
import ListCustomerSearchForm from '@/pages/user/components/list-customer-search-form';
import {
    CustomerListItem,
    CustomerListPagination,
    CustomerListQuery,
    UserOption,
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
    managers?: UserOption[];
    employees?: UserOption[];
    filters?: CustomerListQuery['filter'];
    canFilterManager?: boolean;
    canFilterEmployee?: boolean;
};

const ListCustomer = ({
    paginator,
    managers = [],
    employees = [],
    filters,
    canFilterManager = false,
    canFilterEmployee = false,
}: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const [selectedUser, setSelectedUser] = useState<CustomerListItem | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const managerFilterId = filters?.manager_id ?? null;

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
                accessorKey: 'name',
                header: t('common.name'),
            },
            {
                accessorKey: 'username',
                header: t('common.username'),
            },
            {
                accessorKey: 'email',
                header: t('common.email'),
                cell: (cell) => {
                    return cell.row.original.email || '-';
                },
            },
            {
                accessorKey: 'telegram_id',
                header: t('common.telegram_id'),
                cell: (cell) => {
                    return cell.row.original.telegram_id || '-';
                },
            },
            {
                id: 'managed_by',
                header: t('user.manager_owner', { defaultValue: 'Thuộc quản lý' }),
                cell: ({ row }) => {
                    const owner = row.original.owner;
                    const manager = row.original.manager;

                    // Nếu có filter manager và owner là EMPLOYEE, hiển thị cả employee và manager
                    if (managerFilterId && owner?.role === _UserRole.EMPLOYEE && manager?.username) {
                        return t('user.manager_relation', {
                            employee: owner.username,
                            manager: manager.username,
                        });
                    }

                    // Ưu tiên hiển thị owner (người trực tiếp giới thiệu) nếu có
                    if (owner?.username) {
                        return owner.username;
                    }

                    // Nếu không có owner, hiển thị manager nếu có
                    if (manager?.username) {
                        return manager.username;
                    }

                    return '-';
                },
                meta: {
                    cellClassName: 'min-w-[180px]',
                },
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
                id: 'social',
                header: t('common.social_authentication'),
                cell: (cell) => {
                    const row = cell.row.original;
                    const hasEmail = !!row.email_verified_at;
                    const hasTelegram = !!row.using_telegram;
                    
                    if (hasEmail && hasTelegram) {
                        return <div className="text-sm">{t('user.authenticated_both', { defaultValue: 'Đã xác thực cả 2' })}</div>;
                    }
                    return (
                        <div className="flex flex-col gap-2">
                            {hasEmail && <div className="text-sm">{t('common.using_email')}</div>}
                            {hasTelegram && <div className="text-sm">{t('common.using_telegram')}</div>}
                            {!hasEmail && !hasTelegram && (
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
        [t, actionCell, managerFilterId],
    );

    return (
        <>
            <ListCustomerSearchForm
                managers={managers}
                employees={employees}
                initialFilter={filters}
                showManagerSelect={canFilterManager}
                showEmployeeSelect={canFilterEmployee}
            />
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
