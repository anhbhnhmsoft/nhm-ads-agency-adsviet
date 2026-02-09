import { ReactNode, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useTranslation } from 'react-i18next';
import { Edit, MoreHorizontal, Plus, Trash } from 'lucide-react';
import { router } from '@inertiajs/react';
import { commissions_create_view, commissions_destroy, commissions_edit_view } from '@/routes';
import { EmployeeCommissionItem, CommissionPagination } from '@/pages/commission/types/type';
import { ColumnDef } from '@tanstack/react-table';
import { Separator } from '@/components/ui/separator';
import { DataTable } from '@/components/table/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

type Props = {
    paginator: CommissionPagination;
};

const Index = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<EmployeeCommissionItem | null>(null);

    const handleDelete = () => {
        if (itemToDelete) {
            router.delete(commissions_destroy(itemToDelete.id).url, {
                onFinish: () => {
                    setShowDeleteDialog(false);
                },
            });
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'service':
                return t('commission.type_service', { defaultValue: 'Hoa hồng dịch vụ' });
            case 'spending':
                return t('commission.type_spending', { defaultValue: 'Hoa hồng theo spending' });
            case 'account':
                return t('commission.type_account', { defaultValue: 'Hoa hồng theo bán account' });
            default:
                return type;
        }
    };

    const columns: ColumnDef<EmployeeCommissionItem>[] = useMemo(
        () => [
            {
                accessorKey: 'service_package.name',
                header: t('commission.service_package', { defaultValue: 'Gói dịch vụ' }),
                cell: ({ row }) => {
                    const servicePackage = row.original.service_package;
                    return servicePackage ? servicePackage.name : '-';
                },
            },
            {
                accessorKey: 'type',
                header: t('commission.type', { defaultValue: 'Loại hoa hồng' }),
                cell: ({ row }) => {
                    return (
                        <Badge variant="outline">
                            {getTypeLabel(row.original.type)}
                        </Badge>
                    );
                },
            },
            {
                accessorKey: 'rate',
                header: t('commission.rate', { defaultValue: 'Tỷ lệ (%)' }),
                cell: ({ row }) => {
                    const rate = Number(row.original.rate);
                    return Number.isFinite(rate) ? `${rate.toFixed(2)}%` : row.original.rate;
                },
            },
            {
                accessorKey: 'min_amount',
                header: t('commission.min_amount', { defaultValue: 'Số tiền tối thiểu' }),
                cell: ({ row }) => {
                    const amount = row.original.min_amount;
                    if (!amount) {
                        return t('commission.no_limit', { defaultValue: 'Không giới hạn' });
                    }
                    const num = Number(amount);
                    return Number.isFinite(num) ? `${num.toLocaleString('vi-VN')} USD` : amount;
                },
            },
            {
                accessorKey: 'max_amount',
                header: t('commission.max_amount', { defaultValue: 'Số tiền tối đa' }),
                cell: ({ row }) => {
                    const amount = row.original.max_amount;
                    if (!amount) {
                        return t('commission.no_limit', { defaultValue: 'Không giới hạn' });
                    }
                    const num = Number(amount);
                    return Number.isFinite(num) ? `${num.toLocaleString('vi-VN')} USD` : amount;
                },
            },
            {
                accessorKey: 'is_active',
                header: t('common.status'),
                cell: (cell) => {
                    const isActive = cell.row.original.is_active;
                    return (
                        <Badge variant={isActive ? 'default' : 'destructive'}>
                            {isActive ? t('common.active') : t('common.inactive')}
                        </Badge>
                    );
                },
            },
            {
                id: 'actions',
                header: t('common.action'),
                cell: ({ row }) => {
                    const item = row.original;
                    return (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="h-8 w-8 p-0">
                                    <span className="sr-only">Open menu</span>
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onClick={() =>
                                        router.visit(commissions_edit_view(item.id).url)
                                    }
                                >
                                    <Edit className="mr-2 h-4 w-4" />
                                    <span>{t('common.edit')}</span>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    className="text-red-600"
                                    onClick={() => {
                                        setItemToDelete(item);
                                        setShowDeleteDialog(true);
                                    }}
                                >
                                    <Trash className="mr-2 h-4 w-4" />
                                    <span>{t('common.delete')}</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                },
            },
        ],
        [t],
    );

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {t('commission.index_title', { defaultValue: 'Quản lý hoa hồng' })}
                    </h1>
                    <Button
                        onClick={() => {
                            router.visit(commissions_create_view().url);
                        }}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        {t('commission.create_btn', { defaultValue: 'Tạo cấu hình hoa hồng' })}
                    </Button>
                </div>

                {paginator && paginator.data.length > 0 ? (
                    <>
                        <DataTable columns={columns} paginator={paginator} />
                    </>
                ) : (
                    <Empty>
                        <EmptyMedia>
                            <Plus />
                        </EmptyMedia>
                        <EmptyHeader>
                            <EmptyTitle>
                                {t('commission.empty_title', { defaultValue: 'Chưa có cấu hình hoa hồng nào' })}
                            </EmptyTitle>
                            <EmptyDescription>
                                {t('commission.empty_description', { defaultValue: 'Tạo cấu hình hoa hồng mới để bắt đầu' })}
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button
                                onClick={() => {
                                    router.visit(commissions_create_view().url);
                                }}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {t('commission.create_btn', { defaultValue: 'Tạo cấu hình hoa hồng' })}
                            </Button>
                        </EmptyContent>
                    </Empty>
                )}

                <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {t('common.confirm_delete')}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {t('commission.delete_confirmation', {
                                    defaultValue: 'Bạn có chắc chắn muốn xóa cấu hình hoa hồng này? Hành động này không thể hoàn tác.',
                                })}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>
                                {t('common.cancel')}
                            </AlertDialogCancel>
                            <AlertDialogAction onClick={handleDelete}>
                                {t('common.delete')}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </AppLayout>
    );
};

export default Index;

