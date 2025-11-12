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
import { Edit, MoreHorizontal, PackageOpen, Plus, Trash, ToggleLeft, ToggleRight } from 'lucide-react';
import { router } from '@inertiajs/react';
import { service_packages_create_view, service_packages_destroy, service_packages_edit_view, service_packages_toggle_disable } from '@/routes';
import { ServicePackageItem, ServicePackagePagination } from '@/pages/service-package/types/type';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Avatar, AvatarImage } from '@/components/ui/avatar';

import GoogleIcon from '@/images/google_icon.png';
import FacebookIcon from '@/images/facebook_icon.png';
import { _PlatformType } from '@/lib/types/constants';
import ServicePackageListSearchForm from '@/pages/service-package/components/search-form';
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
    paginator: ServicePackagePagination;
};
const Index = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<ServicePackageItem | null>(null);

    const handleDelete = () => {
        if (itemToDelete) {
            router.delete(service_packages_destroy(itemToDelete.id).url, {
                onFinish: () => {
                    setShowDeleteDialog(false);
                },
            });
        }
    };

    const handleToggleDisable = (item: ServicePackageItem) => {
        router.post(service_packages_toggle_disable(item.id).url, {}, {
            preserveScroll: true,
        });
    };

    const columns: ColumnDef<ServicePackageItem>[] = useMemo(
        () => [
            {
                accessorKey: 'id',
                header: t('common.id'),
            },
            {
                accessorKey: 'name',
                header: t('service_packages.name'),
            },
            {
                accessorKey: 'platform',
                header: t('service_packages.platform'),
                cell: (cell) => {
                    const platform = cell.row.original.platform;
                    return (
                        <>
                            {platform === _PlatformType.GOOGLE && (
                                <Avatar>
                                    <AvatarImage src={GoogleIcon} />
                                </Avatar>
                            )}
                            {platform === _PlatformType.META && (
                                <Avatar>
                                    <AvatarImage src={FacebookIcon} />
                                </Avatar>
                            )}
                        </>
                    );
                },
            },
            {
                accessorKey: 'open_fee',
                header: t('service_packages.open_fee'),
            },
            {
                accessorKey: 'top_up_fee',
                header: t('service_packages.top_up_fee'),
            },
            {
                accessorKey: 'set_up_time',
                header: t('service_packages.set_up_time'),
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
                                            service_packages_edit_view(item.id)
                                                .url,
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
        <div>
            {paginator && paginator.data.length > 0 ? (
                <>
                    <ServicePackageListSearchForm />
                    <Separator className={'my-4'} />
                    <DataTable columns={columns} paginator={paginator} />
                </>
            ) : (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <PackageOpen />
                        </EmptyMedia>
                        <EmptyTitle>
                            {t('service_packages.empty_title')}
                        </EmptyTitle>
                        <EmptyDescription>
                            {t('service_packages.empty_description')}
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button
                            className={'cursor-pointer'}
                            onClick={() => {
                                router.visit(
                                    service_packages_create_view().url,
                                );
                            }}
                        >
                            <Plus />
                            {t('service_packages.create_btn')}
                        </Button>
                    </EmptyContent>
                </Empty>
            )}
            <AlertDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('common.confirm_delete_title')}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {t('common.confirm_delete_description')}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>
                            {t('common.cancel')}
                        </AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>
                            {t('common.confirm')}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
};

Index.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.service_packages' }]}
        children={page}
    />
);

export default Index;
