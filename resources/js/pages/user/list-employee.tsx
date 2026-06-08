import AppLayout from '@/layouts/app-layout';
import { usePage } from '@inertiajs/react';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import useCheckRole from '@/hooks/use-check-role';
import { _UserRole, userRolesLabel } from '@/lib/types/constants';
import AssignEmployee from '@/pages/user/components/assign-employee';
import ListEmployeeSearchForm from '@/pages/user/components/list-employee-search-form';
import { useActionCell } from '@/pages/user/hooks/use-action-cell';
import {
    EmployeeListItem,
    EmployeeListPagination,
    Manager,
} from '@/pages/user/types/type';
import {
    user_employee_destroy,
    user_employee_edit,
    user_employee_toggle_disable,
    user_list_employee,
} from '@/routes';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Check, OctagonX } from 'lucide-react';

type Props = {
    paginator: EmployeeListPagination;
    managers?: Manager[];
};
const ListEmployee = ({ paginator, managers = [] }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const actionCell = useActionCell<EmployeeListItem>({
        canDelete: isAdmin,
        getToggleText: (disabled) =>
            disabled ? t('common.active') : t('common.disabled'),
        onToggle: (employee) => {
            const disabled = !!employee.disabled;
            router.post(
                user_employee_toggle_disable({ id: employee.id }).url,
                { disabled: !disabled },
                { preserveScroll: true },
            );
        },
        onEdit: (employee) => {
            router.visit(user_employee_edit({ id: employee.id }).url);
        },
        onDelete: (employee) => {
            if (confirm(t('user.confirm_delete'))) {
                router.delete(user_employee_destroy({ id: employee.id }).url, {
                    preserveScroll: true,
                });
            }
        },
    });

    const columns: ColumnDef<EmployeeListItem>[] = useMemo(
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
                                <Check className={'size-4 text-green-500'} />
                            ) : (
                                <OctagonX className={'size-4 text-red-500'} />
                            )}
                        </div>
                    );
                },
                meta: {
                    headerClassName: 'text-center',
                },
            },
            {
                id: 'action',
                header: t('common.action'),
                cell: (cell) => actionCell(cell.row.original),
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
            },
        ],
        [t, isAdmin],
    );
    return (
        <div>
            <ListEmployeeSearchForm />
            <Separator className={'my-4'} />
            <Tabs defaultValue="list" className="w-full">
                <TabsList>
                    <TabsTrigger value="list">
                        {t('user.employee_list')}
                    </TabsTrigger>
                    {isAdmin && (
                        <TabsTrigger value="assign">
                            {t('user.assign_employee')}
                        </TabsTrigger>
                    )}
                </TabsList>
                <TabsContent value="list" className="mt-4">
                    <DataTable columns={columns} paginator={paginator} />
                </TabsContent>
                {isAdmin && (
                    <TabsContent value="assign" className="mt-4">
                        <AssignEmployee managers={managers} />
                    </TabsContent>
                )}
            </Tabs>
        </div>
    );
};

ListEmployee.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[
            {
                title: 'menu.user_list_employee',
                href: user_list_employee().url,
            },
        ]}
        children={page}
    />
);
export default ListEmployee;
