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
import { Edit, MoreHorizontal, Plus, Trash, ToggleLeft, ToggleRight } from 'lucide-react';
import { router } from '@inertiajs/react';
import { suppliers_create_view, suppliers_destroy, suppliers_edit_view, suppliers_toggle_disable } from '@/routes';
import { SupplierItem, SupplierPagination } from '@/pages/supplier/types/type';
import SupplierListSearchForm from '@/pages/supplier/components/search-form';
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
    paginator: SupplierPagination;
};

const Index = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<SupplierItem | null>(null);

    const handleDelete = () => {
        if (itemToDelete) {
            router.delete(suppliers_destroy(itemToDelete.id).url, {
                onFinish: () => {
                    setShowDeleteDialog(false);
                },
            });
        }
    };

    const handleToggleDisable = (item: SupplierItem) => {
        router.post(suppliers_toggle_disable(item.id).url, {}, {
            preserveScroll: true,
        });
    };

    const columns: ColumnDef<SupplierItem>[] = useMemo(
        () => [
            {
                accessorKey: 'id',
                header: t('common.id'),
            },
            {
                accessorKey: 'name',
                header: t('supplier.name', { defaultValue: 'Tên nhà cung cấp' }),
            },
            {
                accessorKey: 'open_fee',
                header: t('supplier.open_fee', { defaultValue: 'Chi phí mở tài khoản (trả trước)' }),
                cell: ({ row }) => {
                    const raw = row.original.open_fee;
                    const num = Number(raw);
                    return Number.isFinite(num) ? num.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) + ' USDT' : raw;
                },
            },
            {
                accessorKey: 'postpay_fee',
                header: t('supplier.postpay_fee', { defaultValue: 'Chi phí nhà cung cấp (trả sau)' }),
                cell: ({ row }) => {
                    const raw = row.original.postpay_fee;
                    const num = Number(raw);
                    return Number.isFinite(num) ? num.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) + ' USDT' : raw;
                },
            },
            {
                accessorKey: 'disabled',
                header: t('common.status'),
                cell: (cell) => {
                    const disabled = cell.row.original.disabled;
                    return (
                        <Badge
                            variant={disabled ? 'destructive' : 'default'}
                        >
                            {disabled
                                ? t('common.disabled')
                                : t('common.active')}
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
                                    <span className="sr-only">
                                        Open menu
                                    </span>
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onClick={() =>
                                        router.visit(
                                            suppliers_edit_view(item.id).url,
                                        )
                                    }
                                >
                                    <Edit className="mr-2 h-4 w-4" />
                                    <span>{t('common.edit')}</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => handleToggleDisable(item)}
                                >
                                    {item.disabled ? (
                                        <ToggleRight className="mr-2 h-4 w-4" />
                                    ) : (
                                        <ToggleLeft className="mr-2 h-4 w-4" />
                                    )}
                                    <span>{t('common.toggle_disable')}</span>
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
            <div>
                {paginator && paginator.data.length > 0 ? (
                    <>
                        <SupplierListSearchForm />
                        <Separator className="my-4" />
                        <DataTable columns={columns} paginator={paginator} />
                    </>
                ) : (
                    <Empty>
                        <EmptyMedia>
                            <Plus />
                        </EmptyMedia>
                        <EmptyHeader>
                            <EmptyTitle>
                                {t('supplier.empty_title', { defaultValue: 'Chưa có nhà cung cấp nào' })}
                            </EmptyTitle>
                            <EmptyDescription>
                                {t('supplier.empty_description', { defaultValue: 'Tạo nhà cung cấp mới để bắt đầu' })}
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button
                                onClick={() => {
                                    router.visit(suppliers_create_view().url);
                                }}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {t('supplier.create_btn', { defaultValue: 'Tạo nhà cung cấp' })}
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
                                {t('supplier.delete_confirmation', {
                                    defaultValue: 'Bạn có chắc chắn muốn xóa nhà cung cấp này? Hành động này không thể hoàn tác.',
                                    name: itemToDelete?.name,
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

