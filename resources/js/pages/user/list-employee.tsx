import AppLayout from '@/layouts/app-layout';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';

import { EmployeeListItem, EmployeeListPagination, Manager } from '@/pages/user/types/type';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import { userRolesLabel, _UserRole } from '@/lib/types/constants';
import { Check, OctagonX } from 'lucide-react';
import ListEmployeeSearchForm from '@/pages/user/components/list-employee-search-form';
import { Separator } from '@/components/ui/separator';
import { user_list_employee, user_employee_toggle_disable, user_employee_edit, user_employee_destroy } from '@/routes';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2 } from 'lucide-react';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import AssignEmployee from '@/pages/user/components/assign-employee';
import useCheckRole from '@/hooks/use-check-role';

type Props = {
    paginator: EmployeeListPagination;
    managers?: Manager[];
}
const ListEmployee = ({paginator, managers = []}: Props) => {
    const {t} = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const isManager = checkRole([_UserRole.MANAGER]);
    const columns: ColumnDef<EmployeeListItem>[] = useMemo(() => [
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
            id: 'action',
            header: t('common.action'),
            cell: (cell) => {
                const employee = cell.row.original;
                const disabled = employee.disabled;
                const handleToggle = () => {
                    router.post(
                        user_employee_toggle_disable({ id: employee.id }).url,
                        { disabled: !disabled },
                        {
                            preserveScroll: true,
                        }
                    );
                };
                const handleEdit = () => {
                    router.visit(user_employee_edit({ id: employee.id }).url);
                };
                const handleDelete = () => {
                    if (confirm(t('user.confirm_delete', { defaultValue: 'Bạn có chắc chắn muốn xóa nhân viên này?' }))) {
                        router.delete(
                            user_employee_destroy({ id: employee.id }).url,
                            {
                                preserveScroll: true,
                            }
                        );
                    }
                };
                return (
                    <div className="flex items-center justify-center gap-2">
                        <Button
                            type="button"
                            variant={disabled ? 'default' : 'outline'}
                            size="sm"
                            onClick={handleToggle}
                        >
                            {disabled ? t('common.active') : t('common.disabled')}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={handleEdit}
                        >
                            <Pencil className="size-4" />
                        </Button>
                        {isAdmin && (
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                onClick={handleDelete}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        )}
                    </div>
                );
            },
            meta: {
                headerClassName: 'text-center',
                cellClassName: 'text-center',
            },
        },
    ], [t, isAdmin]);
    return (
        <div>
            <ListEmployeeSearchForm />
            <Separator className={'my-4'} />
            <Tabs defaultValue="list" className="w-full">
                <TabsList>
                    <TabsTrigger value="list">{t('user.employee_list', { defaultValue: 'Danh sách nhân viên' })}</TabsTrigger>
                    {isAdmin && (
                        <TabsTrigger value="assign">{t('user.assign_employee', { defaultValue: 'Gán nhân viên' })}</TabsTrigger>
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
    <AppLayout breadcrumbs={[
        {
            title: 'menu.user_list_employee',
            href: user_list_employee().url,
        },

    ]} children={page} />
);
export default ListEmployee;
