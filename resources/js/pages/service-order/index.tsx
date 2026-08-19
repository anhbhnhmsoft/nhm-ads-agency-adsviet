import { DataTable } from '@/components/table/data-table';
import { TimezoneSelect } from '@/components/timezone-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import useCheckRole from '@/hooks/use-check-role';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType, _UserRole } from '@/lib/types/constants';
import { cn } from '@/lib/utils';
import { AccountFormEdit } from '@/pages/service-order/components/AccountFormEdit';
import { AccountInfoCell } from '@/pages/service-order/components/AccountInfoCell';
import { useServiceOrderAdminDialog } from '@/pages/service-order/hooks/use-admin-approve-dialog';
import { useServiceOrderEditConfigDialog } from '@/pages/service-order/hooks/use-edit-config-dialog';
import type {
    ServiceOrder,
    ServiceOrderPagination,
} from '@/pages/service-order/types/type';
import {
    service_orders_cancel,
    service_orders_destroy,
    service_purchase_index,
} from '@/routes';
import {
    Head,
    router as inertiaRouter,
    Link,
    router,
    usePage,
} from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    ChevronDown,
    Filter,
    Package,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    ShoppingBag,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface SearchableSelectProps {
    value?: string;
    onValueChange: (value: string) => void;
    placeholder: string;
    searchPlaceholder?: string;
    emptyText?: string;
    options: {
        value: string;
        label: string;
        sublabel?: string;
        disabled?: boolean;
    }[];
    disabled?: boolean;
    className?: string;
}

function SearchableSelect({
    value,
    onValueChange,
    placeholder,
    searchPlaceholder = 'Tìm kiếm...',
    emptyText = 'Không tìm thấy kết quả',
    options,
    disabled = false,
    className,
}: SearchableSelectProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const selectedOption = options.find((opt) => opt.value === value);

    const filteredOptions = options.filter((opt) => {
        const query = search.toLowerCase().trim();
        if (!query) return true;
        return (
            opt.label.toLowerCase().includes(query) ||
            (opt.sublabel && opt.sublabel.toLowerCase().includes(query)) ||
            opt.value.toLowerCase().includes(query)
        );
    });

    useEffect(() => {
        if (!open) {
            setSearch('');
        }
    }, [open]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn(
                        'h-10 w-full justify-between border-input bg-background px-3 py-2 text-left font-normal focus:ring-2 focus:ring-ring focus:ring-offset-2',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span className="truncate">
                        {selectedOption
                            ? selectedOption.sublabel
                                ? `${selectedOption.label} (${selectedOption.sublabel})`
                                : selectedOption.label
                            : placeholder}
                    </span>
                    <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-[var(--radix-popover-trigger-width)] p-0"
                align="start"
            >
                <div className="flex items-center border-b px-3">
                    <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={searchPlaceholder}
                        className="flex h-10 w-full rounded-md border-none bg-transparent px-0 py-3 text-sm outline-none focus-visible:ring-0 focus-visible:ring-offset-0"
                    />
                </div>
                <ScrollArea className="max-h-[280px] overflow-y-auto p-1">
                    {filteredOptions.length === 0 ? (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            {emptyText}
                        </div>
                    ) : (
                        <div className="space-y-1">
                            {filteredOptions.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    disabled={opt.disabled}
                                    onClick={() => {
                                        onValueChange(opt.value);
                                        setOpen(false);
                                    }}
                                    className={cn(
                                        'relative flex w-full cursor-default items-center rounded-sm px-2 py-1.5 text-left text-sm transition-colors outline-none select-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                        opt.value === value &&
                                            'bg-accent font-medium text-accent-foreground',
                                        opt.disabled &&
                                            'pointer-events-none opacity-50',
                                    )}
                                >
                                    <span className="block truncate">
                                        {opt.label}
                                        {opt.sublabel && (
                                            <span className="ml-1 block text-xs text-muted-foreground">
                                                {opt.sublabel}
                                            </span>
                                        )}
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}
                </ScrollArea>
            </PopoverContent>
        </Popover>
    );
}

type TimezoneOption = {
    value: string;
    label: string;
};

type Props = {
    paginator: ServiceOrderPagination;
    meta_timezones?: TimezoneOption[];
    google_timezones?: TimezoneOption[];
    customers?: { id: string; name: string; username: string }[];
};

const STATUS_OPTIONS: Array<{ value: string; labelKey: string }> = [
    { value: '', labelKey: 'service_orders.filter.all_status' },
    { value: 'PENDING', labelKey: 'service_orders.filter.status_pending' },
    { value: 'ACTIVE', labelKey: 'service_orders.filter.status_active' },
    {
        value: 'PROCESSING',
        labelKey: 'service_orders.filter.status_processing',
    },
    { value: 'FAILED', labelKey: 'service_orders.filter.status_failed' },
    { value: 'CANCELLED', labelKey: 'service_orders.filter.status_cancelled' },
    {
        value: 'QUEUE_JOB_PENDING',
        labelKey: 'service_orders.filter.status_queue_job_pending',
    },
];

const PLATFORM_OPTIONS: Array<{ value: string; labelKey: string }> = [
    { value: '', labelKey: 'service_orders.filter.all_platforms' },
    { value: '1', labelKey: 'Meta Ads' },
    { value: '2', labelKey: 'Google Ads' },
];

const STATUS_COLORS: Record<string, string> = {
    PENDING: 'bg-amber-500 text-white',
    QUEUE_JOB_PENDING: 'bg-[#4285f4] text-white',
    QUEUE_JOB_ON_PROCESS: 'bg-[#4285f4] text-white',
    PROCESSING: 'bg-indigo-500 text-white',
    ACTIVE: 'bg-green-500 text-white',
    FAILED: 'bg-red-500 text-white',
    CANCELLED: 'bg-gray-500 text-white',
};

const ServiceOrdersIndex = ({
    paginator,
    meta_timezones = [],
    google_timezones = [],
    customers = [],
}: Props) => {
    const { t } = useTranslation();
    const { props, url } = usePage();
    const checkRole = useCheckRole(props.auth);
    const canViewFinancials = checkRole([_UserRole.ADMIN]);
    const is_admin_view = checkRole([
        _UserRole.ADMIN,
        _UserRole.MANAGER,
        _UserRole.EMPLOYEE,
    ]);
    const orders = paginator?.data ?? [];

    // Multi-input lists for approve dialog
    const [bmIdList, setBmIdList] = useState<string[]>(['']);
    const [accountIdList, setAccountIdList] = useState<string[]>(['']);

    // Multi-input lists for edit dialog (BM/fanpage/website)
    const [editBmIdList, setEditBmIdList] = useState<string[]>(['']);
    const [editFanpageList, setEditFanpageList] = useState<string[]>(['']);
    const [editWebsiteList, setEditWebsiteList] = useState<string[]>(['']);

    // Filter state
    const [filterSearch, setFilterSearch] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [filterPlatform, setFilterPlatform] = useState('');
    const [filterUserId, setFilterUserId] = useState('');

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        setFilterSearch(params.get('filter[search]') || '');
        setFilterStatus(params.get('filter[status]') || '');
        setFilterPlatform(params.get('filter[platform]') || '');
        setFilterUserId(params.get('filter[user_id]') || '');
    }, [url]);

    const [searchBmQuery, setSearchBmQuery] = useState('');
    const [searchAccountQuery, setSearchAccountQuery] = useState('');

    // Derived accountIdInput = first non-empty item in accountIdList
    const currentAccountId = accountIdList.find((v) => v.trim()) || '';

    const {
        dialogOpen,
        setDialogOpen,
        selectedOrder,
        useAccountsStructure: approveUseAccountsStructure,
        accounts: approveAccounts,
        setAccounts: setApproveAccounts,
        metaEmail,
        setMetaEmail,
        displayName,
        setDisplayName,
        bmId,
        setBmId,
        paymentType,
        setPaymentType,
        assetAccess,
        setAssetAccess,
        timezoneBm,
        setTimezoneBm,
        bmAccounts,
        loadingBmAccounts,
        bmList,
        loadingBmList,
        handleSelectBmFromList,
        accountIdInput,
        setAccountIdInput,
        handleSelectAccountFromList,
        assignMode,
        setAssignMode,
        openDialogForOrder,
        handleSubmitApprove,
        formErrors,
        processing: approveProcessing,
    } = useServiceOrderAdminDialog();

    useEffect(() => {
        if (!dialogOpen) {
            setSearchBmQuery('');
            setSearchAccountQuery('');
        }
    }, [dialogOpen]);

    useEffect(() => {
        setSearchAccountQuery('');
    }, [bmId]);

    const {
        dialogOpen: editDialogOpen,
        setDialogOpen: setEditDialogOpen,
        selectedOrder: selectedEditOrder,
        useAccountsStructure: editUseAccountsStructure,
        accounts: editAccounts,
        setAccounts: setEditAccounts,
        metaEmail: editMetaEmail,
        setMetaEmail: setEditMetaEmail,
        displayName: editDisplayName,
        setDisplayName: setEditDisplayName,
        bmId: editBmId,
        setBmId: setEditBmId,
        infoFanpage: editInfoFanpage,
        setInfoFanpage: setEditInfoFanpage,
        infoWebsite: editInfoWebsite,
        setInfoWebsite: setEditInfoWebsite,
        paymentType: editPaymentType,
        setPaymentType: setEditPaymentType,
        assetAccess: editAssetAccess,
        setAssetAccess: setEditAssetAccess,
        timezoneBm: editTimezoneBm,
        setTimezoneBm: setEditTimezoneBm,
        openDialogForOrder: openEditDialogForOrder,
        handleSubmitUpdate,
        assignMode: editAssignMode,
        setAssignMode: setEditAssignMode,
        bmList: editBmList,
        loadingBmList: editLoadingBmList,
        bmAccounts: editBmAccounts,
        loadingBmAccounts: editLoadingBmAccounts,
        accountIdInput: editAccountIdInput,
        setAccountIdInput: setEditAccountIdInput,
        // accountIdList từ hook (multi-account fix)
        accountIdList: editAccountIdList,
        setAccountIdList: setEditAccountIdList,
        handleSelectBmFromList: handleEditSelectBmFromList,
        handleSelectAccountFromList: handleEditSelectAccountFromList,
    } = useServiceOrderEditConfigDialog();

    const isApproveMeta =
        selectedOrder?.package?.platform === _PlatformType.META;

    // Helper: thêm vào list, bỏ trống đầu tiên, check trùng
    const addToListUnique = (
        list: string[],
        setList: React.Dispatch<React.SetStateAction<string[]>>,
        value: string,
    ) => {
        const trimmed = value.trim();
        if (!trimmed) return;
        setList((prev) => {
            const exists = prev.some((v) => v.trim() === trimmed);
            if (exists) return prev;
            const firstEmpty = prev.findIndex((v) => !v.trim());
            if (firstEmpty >= 0) {
                const newList = [...prev];
                newList[firstEmpty] = trimmed;
                return newList;
            }
            return [...prev, trimmed];
        });
    };

    // Reset multi-input lists khi dialog mở
    useEffect(() => {
        if (dialogOpen) {
            setBmIdList([bmId || '']);
            setAccountIdList([accountIdInput || '']);
        }
    }, [dialogOpen]);

    // Reset multi-input lists khi edit dialog mở
    useEffect(() => {
        if (editDialogOpen && selectedEditOrder) {
            const config = selectedEditOrder.config_account || {};
            const configAccounts = Array.isArray(config.accounts) ? config.accounts : [];

            const resolvedBmIds = Array.isArray(config.resolved_bm_ids)
                ? config.resolved_bm_ids.filter(Boolean)
                : [];
            const accBmIds = configAccounts.flatMap((a: any) => a.bm_ids || []);
            const allBmIds = Array.from(new Set([
                ...(config.bm_id ? [config.bm_id] : []),
                ...resolvedBmIds,
                ...accBmIds,
            ])).filter(Boolean);

            setEditBmIdList(allBmIds.length > 0 ? allBmIds : ['']);

            const resolvedAccountIds = Array.isArray(config.resolved_account_ids)
                ? config.resolved_account_ids.filter(Boolean)
                : [];
            const accAccountIds = configAccounts.map((a: any) => a.account_id).filter(Boolean);
            const rawConfigAccountIds = Array.isArray(config.account_ids)
                ? config.account_ids.filter(Boolean)
                : [];
            const accountIdVal = config.account_id || accAccountIds[0] || '';

            const accountIdsVal = Array.from(new Set([
                ...resolvedAccountIds,
                ...rawConfigAccountIds,
                ...accAccountIds,
                ...(accountIdVal ? [accountIdVal] : []),
            ])).filter(Boolean);

            setEditAccountIdList(
                accountIdsVal.length > 0 ? accountIdsVal : [''],
            );

            const accFanpages = configAccounts.flatMap((a: any) => a.fanpages || (a.info_fanpage ? [a.info_fanpage] : []));
            const allFanpages = Array.from(new Set([
                ...(config.info_fanpage ? [config.info_fanpage] : []),
                ...accFanpages,
            ])).filter(Boolean);
            setEditFanpageList(allFanpages.length > 0 ? allFanpages : ['']);

            const accWebsites = configAccounts.flatMap((a: any) => a.websites || (a.info_website ? [a.info_website] : []));
            const allWebsites = Array.from(new Set([
                ...(config.info_website ? [config.info_website] : []),
                ...accWebsites,
            ])).filter(Boolean);
            setEditWebsiteList(allWebsites.length > 0 ? allWebsites : ['']);
        }
    }, [editDialogOpen, selectedEditOrder, setEditAccountIdList]);

    const isEditMeta =
        selectedEditOrder?.package?.platform === _PlatformType.META;

    const getStatusInfo = useCallback(
        (statusLabel?: string | null) => {
            if (!statusLabel)
                return {
                    label: t('service_orders.status.unknown'),
                    className: 'bg-muted',
                };
            const className = STATUS_COLORS[statusLabel] || 'bg-muted';
            const label = t(
                `service_orders.status.${statusLabel.toLowerCase()}`,
                { defaultValue: statusLabel },
            );
            return { className, label };
        },
        [t],
    );

    const formatDateTime = (value?: string | null) => {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${hours}:${minutes} - ${day}/${month}/${year}`;
    };

    const columns = useMemo<ColumnDef<ServiceOrder>[]>(() => {
        const baseColumns: ColumnDef<ServiceOrder>[] = [
            {
                header: t('service_orders.table.stt'),
                cell: ({ row }) => row.index + 1,
                meta: { headerClassName: 'w-[60px]' },
            },
            {
                accessorKey: 'id',
                header: t('service_orders.table.order_id'),
                cell: ({ getValue }) => (
                    <span className="font-mono text-xs">
                        {String(getValue())}
                    </span>
                ),
            },
            {
                id: 'service_package',
                header: t('service_orders.table.package', {
                    defaultValue: 'Gói dịch vụ',
                }),
                cell: ({ row }) => {
                    const packageName = row.original.package?.name;
                    const platformLabel = row.original.package?.platform_label;
                    return (
                        <span className="text-xs">
                            {packageName || '-'}
                            {platformLabel && (
                                <span className="ml-1 text-muted-foreground">
                                    ({platformLabel})
                                </span>
                            )}
                        </span>
                    );
                },
            },
            {
                id: 'customer_name',
                header: t('service_orders.table.customer_name'),
                cell: ({ row }) => {
                    const name = row.original.user?.name || '';
                    return name ? (
                        <span className="text-xs">{name}</span>
                    ) : (
                        <span className="text-xs text-muted-foreground">-</span>
                    );
                },
            },
            {
                id: 'referral',
                header: t('service_orders.table.referral'),
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
                cell: ({ row }) => {
                    const referrerName =
                        row.original.user?.referrer?.name || '';
                    return referrerName ? (
                        <span className="text-xs">{referrerName}</span>
                    ) : (
                        <span className="text-xs text-muted-foreground">-</span>
                    );
                },
            },
            {
                id: 'account_info',
                header: t('service_orders.table.account_info'),
                meta: {
                    headerClassName:
                        'w-[475px] min-w-[475px] max-w-[475px] break-words whitespace-normal',
                    cellClassName:
                        'w-[475px] min-w-[475px] max-w-[475px] break-words whitespace-normal',
                },
                cell: ({ row }) => {
                    return (
                        <AccountInfoCell
                            config={row.original.config_account || null}
                            platform={row.original.package?.platform}
                            packageBillingSource={
                                row.original.package?.billing_source
                            }
                            metaTimezones={meta_timezones}
                            googleTimezones={google_timezones}
                        />
                    );
                },
            },
            {
                id: 'platform',
                header: t('service_orders.table.platform'),
                cell: ({ row }) => row.original.package?.platform_label || '-',
            },
            {
                id: 'topup',
                header: t('service_orders.table.top_up_amount'),
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) => {
                    const config = row.original.config_account || {};
                    const paymentType = (
                        (config.payment_type as string) || ''
                    ).toLowerCase();
                    const topupRaw = config.top_up_amount as
                        number | string | undefined;
                    const isTopupMissing =
                        topupRaw === undefined ||
                        topupRaw === null ||
                        topupRaw === '';
                    const isPostpay =
                        paymentType === 'postpay' ||
                        (paymentType === '' && isTopupMissing);

                    if (isPostpay) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                {t('service_orders.table.postpay_label')}
                            </span>
                        );
                    }

                    if (isTopupMissing) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                -
                            </span>
                        );
                    }
                    const num = Number(topupRaw);
                    if (Number.isNaN(num)) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                -
                            </span>
                        );
                    }
                    return (
                        <span className="text-xs font-medium">
                            {num.toFixed(2)} USDT
                        </span>
                    );
                },
            },
            {
                id: 'total_cost',
                header: t('service_orders.table.total_cost'),
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) => {
                    const totalCost = row.original.total_cost;

                    if (
                        totalCost === undefined ||
                        totalCost === null ||
                        totalCost === 0
                    ) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                -
                            </span>
                        );
                    }

                    return (
                        <span className="text-xs font-medium">
                            {Number(totalCost).toFixed(2)} USDT
                        </span>
                    );
                },
            },
            {
                id: 'budget',
                header: t('service_orders.table.budget'),
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) => {
                    const budget = row.original.budget;
                    if (!budget) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                {t('service_orders.table.budget_unlimited')}
                            </span>
                        );
                    }
                    const budgetValue = parseFloat(budget);
                    if (Number.isNaN(budgetValue)) {
                        return (
                            <span className="text-xs text-muted-foreground">
                                -
                            </span>
                        );
                    }
                    if (budgetValue === 0) {
                        return (
                            <span className="text-xs font-medium text-muted-foreground">
                                {t('service_orders.table.budget_unlimited')}
                            </span>
                        );
                    }
                    return (
                        <span className="text-xs font-medium">
                            {budgetValue.toFixed(2)} USD
                        </span>
                    );
                },
            },
            {
                id: 'status',
                header: t('service_orders.table.status'),
                meta: {
                    headerClassName: 'text-center',
                    cellClassName: 'text-center',
                },
                cell: ({ row }) => {
                    const info = getStatusInfo(row.original.status_label);
                    return (
                        <Badge className={info.className}>{info.label}</Badge>
                    );
                },
            },
            {
                accessorKey: 'created_at',
                header: t('service_orders.table.created_at'),
                cell: ({ getValue }) => (
                    <span className="text-xs text-muted-foreground">
                        {formatDateTime(getValue() as string | null)}
                    </span>
                ),
            },
        ];

        if (is_admin_view) {
            baseColumns.push({
                id: 'actions',
                header: t('service_orders.table.actions'),
                cell: ({ row }) => {
                    const order = row.original;
                    const isPending = order.status_label === 'PENDING';

                    const handleApprove = () => {
                        openDialogForOrder(order);
                    };

                    const handleCancel = () => {
                        if (
                            !window.confirm(t('service_orders.confirm_cancel'))
                        ) {
                            return;
                        }
                        router.post(
                            service_orders_cancel({ id: order.id }).url,
                            {},
                            { preserveScroll: true },
                        );
                    };

                    const handleEdit = () => {
                        openEditDialogForOrder(order);
                    };

                    const handleDelete = () => {
                        if (
                            !window.confirm(t('service_orders.confirm_delete'))
                        ) {
                            return;
                        }
                        router.delete(
                            service_orders_destroy({ id: order.id }).url,
                            { preserveScroll: true },
                        );
                    };

                    return (
                        <div className="flex gap-2">
                            {isPending && (
                                <>
                                    <Button
                                        size="sm"
                                        variant="default"
                                        onClick={handleApprove}
                                    >
                                        {t('service_orders.actions.approve')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={handleCancel}
                                    >
                                        {t('service_orders.actions.cancel')}
                                    </Button>
                                </>
                            )}
                            {!isPending && order.status === 6 && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="border-blue-300 text-blue-600 hover:bg-blue-50"
                                    onClick={handleApprove}
                                >
                                    <RefreshCw className="mr-1 h-3 w-3" />
                                    {t('service_orders.actions.reassign', {
                                        defaultValue: 'Gán lại',
                                    })}
                                </Button>
                            )}
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={handleEdit}
                            >
                                <Pencil className="mr-1 h-3 w-3" />
                                {t('service_orders.actions.edit')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={handleDelete}
                                className="text-red-600 hover:bg-red-50 hover:text-red-700"
                            >
                                <Trash2 className="mr-1 h-3 w-3" />
                                {t('service_orders.actions.delete')}
                            </Button>
                        </div>
                    );
                },
            });
        }

        return canViewFinancials
            ? baseColumns
            : baseColumns.filter((column) => column.id !== 'total_cost');
    }, [
        t,
        is_admin_view,
        canViewFinancials,
        openDialogForOrder,
        openEditDialogForOrder,
        getStatusInfo,
    ]);

    return (
        <AppLayout>
            <Head
                title={t(
                    is_admin_view
                        ? 'service_orders.admin_title'
                        : 'service_orders.title',
                )}
            />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t(
                                is_admin_view
                                    ? 'service_orders.admin_title'
                                    : 'service_orders.title',
                            )}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                is_admin_view
                                    ? 'service_orders.admin_subtitle'
                                    : 'service_orders.subtitle',
                            )}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {!is_admin_view && (
                            <Button asChild>
                                <Link href={service_purchase_index().url}>
                                    <ShoppingBag className="mr-2 h-4 w-4" />
                                    {t('service_orders.go_to_packages')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filter Bar - Compact Filament style */}
                {is_admin_view && (
                    <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/30 px-3 py-2">
                        <Filter className="h-4 w-4 shrink-0 text-muted-foreground" />

                        {/* Tìm kiếm tên khách */}
                        <div className="relative">
                            <Search className="absolute top-1/2 left-2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="text"
                                className="h-8 w-[180px] rounded-md border bg-background pr-2 pl-7 text-xs outline-none focus:ring-2 focus:ring-ring"
                                placeholder={t(
                                    'service_orders.filter.search_placeholder',
                                )}
                                value={filterSearch}
                                onChange={(e) =>
                                    setFilterSearch(e.target.value)
                                }
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        inertiaRouter.get(
                                            window.location.pathname,
                                            {
                                                'filter[search]':
                                                    filterSearch || undefined,
                                                'filter[status]':
                                                    filterStatus || undefined,
                                                'filter[platform]':
                                                    filterPlatform || undefined,
                                                'filter[user_id]':
                                                    filterUserId || undefined,
                                                page: 1,
                                            } as any,
                                            {
                                                preserveState: true,
                                                replace: true,
                                            },
                                        );
                                    }
                                }}
                            />
                        </div>

                        {/* Lọc khách hàng */}
                        <div className="w-[200px]">
                            <SearchableSelect
                                options={[
                                    {
                                        value: '',
                                        label: t(
                                            'service_orders.filter.all_customers',
                                        ),
                                    },
                                    ...customers.map((c) => ({
                                        value: c.id,
                                        label: c.name,
                                        sublabel: c.username,
                                    })),
                                ]}
                                value={filterUserId}
                                onValueChange={(val) => setFilterUserId(val)}
                                placeholder={t(
                                    'service_orders.filter.customer_placeholder',
                                )}
                                searchPlaceholder={t(
                                    'service_orders.filter.search_customer',
                                )}
                                className="h-8 text-xs"
                            />
                        </div>

                        {/* Lọc trạng thái */}
                        <select
                            className="h-8 rounded-md border bg-background px-2 text-xs outline-none focus:ring-2 focus:ring-ring"
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                        >
                            {STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {t(opt.labelKey)}
                                </option>
                            ))}
                        </select>

                        {/* Lọc platform */}
                        <select
                            className="h-8 rounded-md border bg-background px-2 text-xs outline-none focus:ring-2 focus:ring-ring"
                            value={filterPlatform}
                            onChange={(e) => setFilterPlatform(e.target.value)}
                        >
                            {PLATFORM_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {t(opt.labelKey)}
                                </option>
                            ))}
                        </select>

                        {/* Nút tìm kiếm */}
                        <Button
                            size="sm"
                            variant="default"
                            className="h-8 px-3 text-xs"
                            onClick={() => {
                                inertiaRouter.get(
                                    window.location.pathname,
                                    {
                                        'filter[search]':
                                            filterSearch || undefined,
                                        'filter[status]':
                                            filterStatus || undefined,
                                        'filter[platform]':
                                            filterPlatform || undefined,
                                        'filter[user_id]':
                                            filterUserId || undefined,
                                        page: 1,
                                    } as any,
                                    { preserveState: true, replace: true },
                                );
                            }}
                        >
                            <Search className="mr-1 h-3 w-3" />
                            {t('service_orders.filter.search_button')}
                        </Button>

                        {/* Nút reset - chỉ hiện khi có filter đang active */}
                        {(filterSearch ||
                            filterStatus ||
                            filterPlatform ||
                            filterUserId) && (
                            <Button
                                size="sm"
                                variant="ghost"
                                className="h-8 px-2 text-xs"
                                onClick={() => {
                                    setFilterSearch('');
                                    setFilterStatus('');
                                    setFilterPlatform('');
                                    setFilterUserId('');
                                    inertiaRouter.get(
                                        window.location.pathname,
                                        { page: 1 } as any,
                                        { preserveState: true, replace: true },
                                    );
                                }}
                            >
                                <X className="mr-1 h-3 w-3" />
                                {t('service_orders.filter.reset_button')}
                            </Button>
                        )}
                    </div>
                )}

                <Card className="py-0">
                    {orders.length === 0 ? (
                        <CardContent className="py-12 text-center">
                            <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                {t('service_orders.empty')}
                            </p>
                        </CardContent>
                    ) : (
                        <CardContent className="p-0">
                            <DataTable<ServiceOrder, unknown>
                                columns={columns}
                                paginator={paginator}
                            />
                        </CardContent>
                    )}
                </Card>

                {is_admin_view && (
                    <>
                        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                            <DialogContent className="flex max-h-[90vh] flex-col">
                                <DialogHeader>
                                    <DialogTitle>
                                        {t('service_orders.admin_form_title')}
                                    </DialogTitle>
                                    <DialogDescription>
                                        {t(
                                            'service_orders.admin_form_description',
                                        )}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="flex-1 space-y-4 overflow-y-auto py-2 pr-2">
                                    {selectedOrder?.config_account
                                        ?.top_up_amount != null && (
                                        <div className="rounded-md border border-primary/40 bg-primary/5 px-3 py-2 text-sm">
                                            <span className="font-medium text-muted-foreground">
                                                {t(
                                                    'service_orders.table.top_up_amount',
                                                )}
                                                :
                                            </span>{' '}
                                            <span className="font-semibold text-primary">
                                                {Number(
                                                    selectedOrder.config_account
                                                        .top_up_amount,
                                                ).toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                })}{' '}
                                                USD
                                            </span>
                                        </div>
                                    )}
                                    <div className="space-y-2">
                                        <Label htmlFor="payment_type">
                                            {t('service_purchase.payment_type')}
                                        </Label>
                                        <div className="flex gap-2">
                                            <Button
                                                type="button"
                                                variant={
                                                    paymentType === 'prepay'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={
                                                    paymentType !== 'prepay'
                                                }
                                                onClick={() =>
                                                    setPaymentType('prepay')
                                                }
                                            >
                                                {t(
                                                    'service_purchase.payment_prepay',
                                                )}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant={
                                                    paymentType === 'postpay'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={
                                                    paymentType !== 'postpay'
                                                }
                                                onClick={() =>
                                                    setPaymentType('postpay')
                                                }
                                            >
                                                {t(
                                                    'service_purchase.payment_postpay',
                                                )}
                                            </Button>
                                        </div>
                                    </div>

                                    <>
                                        {/* Tabs: Gán BM / Gán tài khoản */}
                                        <div className="space-y-2">
                                            <div className="flex gap-1 rounded-lg border p-1">
                                                <button
                                                    type="button"
                                                    className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                                        assignMode === 'bm'
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'text-muted-foreground hover:bg-muted'
                                                    }`}
                                                    onClick={() =>
                                                        setAssignMode('bm')
                                                    }
                                                >
                                                    {isApproveMeta
                                                        ? t(
                                                              'service_orders.form.assign_bm',
                                                          )
                                                        : t(
                                                              'service_orders.form.assign_mcc',
                                                          )}
                                                </button>
                                                <button
                                                    type="button"
                                                    className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                                        assignMode === 'account'
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'text-muted-foreground hover:bg-muted'
                                                    }`}
                                                    onClick={() => {
                                                        setAssignMode(
                                                            'account',
                                                        );
                                                        if (bmId)
                                                            handleSelectBmFromList(
                                                                bmId,
                                                            );
                                                    }}
                                                >
                                                    {t(
                                                        'service_orders.form.assign_account',
                                                    )}
                                                </button>
                                            </div>
                                        </div>

                                        {assignMode === 'bm' ? (
                                            <>
                                                {/* ==== TAB GÁN BM ==== */}

                                                {/* Dropdown chọn BM có sẵn */}
                                                <div className="space-y-2">
                                                    <Label>
                                                        {isApproveMeta
                                                            ? t(
                                                                  'service_orders.form.select_bm_available',
                                                              )
                                                            : t(
                                                                  'service_orders.form.select_mcc_available',
                                                              )}
                                                    </Label>
                                                    <SearchableSelect
                                                        key={`bm-tab-bm-${bmIdList.join(',')}`}
                                                        options={bmList
                                                            .filter(
                                                                (bm) =>
                                                                    !bmIdList.some(
                                                                        (id) =>
                                                                            id.trim() ===
                                                                            (bm
                                                                                .bm_ids?.[0] ||
                                                                                bm.id),
                                                                    ),
                                                            )
                                                            .map((bm) => ({
                                                                value:
                                                                    bm
                                                                        .bm_ids?.[0] ||
                                                                    bm.id,
                                                                label:
                                                                    bm.bm_name ||
                                                                    bm.name,
                                                                sublabel:
                                                                    bm
                                                                        .bm_ids?.[0] ||
                                                                    bm.id,
                                                            }))}
                                                        value=""
                                                        onValueChange={(
                                                            value,
                                                        ) => {
                                                            if (value) {
                                                                handleSelectBmFromList(
                                                                    value,
                                                                );
                                                                addToListUnique(
                                                                    bmIdList,
                                                                    setBmIdList,
                                                                    value,
                                                                );
                                                            }
                                                        }}
                                                        placeholder={
                                                            loadingBmList
                                                                ? t(
                                                                      'service_orders.form.loading_child_bms',
                                                                  )
                                                                : isApproveMeta
                                                                  ? t(
                                                                        'service_orders.form.select_bm_from_list',
                                                                    )
                                                                  : t(
                                                                        'service_orders.form.select_mcc_from_list',
                                                                    )
                                                        }
                                                        searchPlaceholder={
                                                            isApproveMeta
                                                                ? t(
                                                                      'service_orders.form.filter_bm_placeholder',
                                                                      {
                                                                          defaultValue:
                                                                              'Lọc danh sách BM...',
                                                                      },
                                                                  )
                                                                : t(
                                                                      'service_orders.form.filter_mcc_placeholder',
                                                                      {
                                                                          defaultValue:
                                                                              'Lọc danh sách MCC...',
                                                                      },
                                                                  )
                                                        }
                                                        disabled={loadingBmList}
                                                    />
                                                </div>

                                                {/* Input ID BM nhập tay + nút thêm */}
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between">
                                                        <Label>
                                                            {isApproveMeta
                                                                ? t(
                                                                      'service_orders.form.bm_id_label',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.mcc_id_label',
                                                                  )}
                                                        </Label>
                                                        {bmIdList.length <
                                                            999 && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-7 text-xs"
                                                                onClick={() =>
                                                                    setBmIdList(
                                                                        [
                                                                            ...bmIdList,
                                                                            '',
                                                                        ],
                                                                    )
                                                                }
                                                            >
                                                                <Plus className="mr-1 h-3 w-3" />
                                                                {isApproveMeta
                                                                    ? t(
                                                                          'service_orders.form.add_bm',
                                                                      )
                                                                    : t(
                                                                          'service_orders.form.add_mcc',
                                                                      )}
                                                            </Button>
                                                        )}
                                                    </div>
                                                    <div className="space-y-2">
                                                        {bmIdList.map(
                                                            (val, idx) => (
                                                                <div
                                                                    key={`bm-${idx}`}
                                                                    className="flex gap-2"
                                                                >
                                                                    <Input
                                                                        value={
                                                                            val
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            const newList =
                                                                                [
                                                                                    ...bmIdList,
                                                                                ];
                                                                            newList[
                                                                                idx
                                                                            ] =
                                                                                e.target.value;
                                                                            setBmIdList(
                                                                                newList,
                                                                            );
                                                                            if (
                                                                                idx ===
                                                                                0
                                                                            )
                                                                                setBmId(
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                );
                                                                        }}
                                                                        placeholder={
                                                                            isApproveMeta
                                                                                ? t(
                                                                                      'service_orders.form.enter_bm_id',
                                                                                  )
                                                                                : t(
                                                                                      'service_orders.form.enter_mcc_id',
                                                                                  )
                                                                        }
                                                                    />
                                                                    {bmIdList.length >
                                                                        1 && (
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="text-red-600"
                                                                            onClick={() =>
                                                                                setBmIdList(
                                                                                    bmIdList.filter(
                                                                                        (
                                                                                            _,
                                                                                            i,
                                                                                        ) =>
                                                                                            i !==
                                                                                            idx,
                                                                                    ),
                                                                                )
                                                                            }
                                                                        >
                                                                            <X className="h-4 w-4" />
                                                                        </Button>
                                                                    )}
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                    {formErrors.bm_id && (
                                                        <p className="text-xs text-red-500">
                                                            {formErrors.bm_id}
                                                        </p>
                                                    )}
                                                </div>
                                            </>
                                        ) : (
                                            <>
                                                {/* ==== TAB GÁN TÀI KHOẢN ==== */}

                                                {/* 1. Dropdown chọn BM có sẵn */}
                                                <div className="space-y-2">
                                                    <Label htmlFor="select_bm_from_list_account">
                                                        {isApproveMeta
                                                            ? t(
                                                                  'service_orders.form.select_bm_available',
                                                              )
                                                            : t(
                                                                  'service_orders.form.select_mcc_available',
                                                              )}
                                                    </Label>
                                                    <SearchableSelect
                                                        key={`account-tab-bm-${bmId}`}
                                                        options={bmList.map(
                                                            (bm) => ({
                                                                value:
                                                                    bm
                                                                        .bm_ids?.[0] ||
                                                                    bm.id,
                                                                label:
                                                                    bm.bm_name ||
                                                                    bm.name,
                                                                sublabel:
                                                                    bm
                                                                        .bm_ids?.[0] ||
                                                                    bm.id,
                                                            }),
                                                        )}
                                                        value={bmId || ''}
                                                        onValueChange={(
                                                            value,
                                                        ) => {
                                                            if (value) {
                                                                handleSelectBmFromList(
                                                                    value,
                                                                );
                                                                addToListUnique(
                                                                    bmIdList,
                                                                    setBmIdList,
                                                                    value,
                                                                );
                                                            }
                                                        }}
                                                        placeholder={
                                                            loadingBmList
                                                                ? t(
                                                                      'service_orders.form.loading_child_bms',
                                                                  )
                                                                : isApproveMeta
                                                                  ? t(
                                                                        'service_orders.form.select_bm_from_list',
                                                                    )
                                                                  : t(
                                                                        'service_orders.form.select_mcc_from_list',
                                                                    )
                                                        }
                                                        searchPlaceholder={
                                                            isApproveMeta
                                                                ? t(
                                                                      'service_orders.form.filter_bm_placeholder',
                                                                      {
                                                                          defaultValue:
                                                                              'Lọc danh sách BM...',
                                                                      },
                                                                  )
                                                                : t(
                                                                      'service_orders.form.filter_mcc_placeholder',
                                                                      {
                                                                          defaultValue:
                                                                              'Lọc danh sách MCC...',
                                                                      },
                                                                  )
                                                        }
                                                        disabled={loadingBmList}
                                                    />
                                                </div>

                                                {/* 2. Dropdown chọn tài khoản có sẵn - BẮT BUỘC */}
                                                <div className="space-y-2">
                                                    <Label
                                                        htmlFor="select_account_from_list"
                                                        className="text-destructive"
                                                    >
                                                        {t(
                                                            'service_orders.form.select_account_label',
                                                        )}{' '}
                                                        *
                                                    </Label>
                                                    <SearchableSelect
                                                        options={bmAccounts
                                                            .filter(
                                                                (acc) =>
                                                                    !accountIdList.some(
                                                                        (id) =>
                                                                            id.trim() ===
                                                                            acc.account_id,
                                                                    ),
                                                            )
                                                            .map((acc) => ({
                                                                value: acc.account_id,
                                                                label: `${acc.account_name || acc.account_id} — ${acc.account_id} (${acc.currency})${acc.service_user_id ? ' [Đã gán]' : ''}`,
                                                                sublabel:
                                                                    acc.account_id,
                                                            }))}
                                                        value=""
                                                        onValueChange={(
                                                            value,
                                                        ) => {
                                                            if (value) {
                                                                addToListUnique(
                                                                    accountIdList,
                                                                    setAccountIdList,
                                                                    value,
                                                                );
                                                                setAccountIdInput(
                                                                    value,
                                                                );
                                                            }
                                                        }}
                                                        placeholder={
                                                            !bmId
                                                                ? t(
                                                                      'service_orders.form.select_bm_first',
                                                                  )
                                                                : loadingBmAccounts
                                                                  ? t(
                                                                        'service_orders.form.loading_child_bms',
                                                                    )
                                                                  : t(
                                                                        'service_orders.form.select_account_in_bm_mcc',
                                                                    )
                                                        }
                                                        searchPlaceholder={t(
                                                            'service_orders.form.search_account_placeholder',
                                                            {
                                                                defaultValue:
                                                                    'Tìm kiếm tài khoản...',
                                                            },
                                                        )}
                                                        disabled={
                                                            loadingBmAccounts ||
                                                            !bmId
                                                        }
                                                    />
                                                    {formErrors.account_id && (
                                                        <p className="text-xs text-red-500">
                                                            {
                                                                formErrors.account_id
                                                            }
                                                        </p>
                                                    )}
                                                </div>

                                                {/* 3. Input ID tài khoản nhập tay + nút thêm */}
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between">
                                                        <Label className="text-destructive">
                                                            {t(
                                                                'service_orders.form.account_id_label',
                                                            )}{' '}
                                                            *
                                                        </Label>
                                                        {accountIdList.length <
                                                            999 && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-7 text-xs"
                                                                onClick={() =>
                                                                    setAccountIdList(
                                                                        [
                                                                            ...accountIdList,
                                                                            '',
                                                                        ],
                                                                    )
                                                                }
                                                            >
                                                                <Plus className="mr-1 h-3 w-3" />
                                                                {t(
                                                                    'service_orders.form.add_account',
                                                                )}
                                                            </Button>
                                                        )}
                                                    </div>
                                                    <div className="space-y-2">
                                                        {accountIdList.map(
                                                            (val, idx) => (
                                                                <div
                                                                    key={`acc-${idx}`}
                                                                    className="flex gap-2"
                                                                >
                                                                    <Input
                                                                        value={
                                                                            val
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            const newList =
                                                                                [
                                                                                    ...accountIdList,
                                                                                ];
                                                                            newList[
                                                                                idx
                                                                            ] =
                                                                                e.target.value;
                                                                            setAccountIdList(
                                                                                newList,
                                                                            );
                                                                            setAccountIdInput(
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            );
                                                                        }}
                                                                        placeholder={
                                                                            isApproveMeta
                                                                                ? 'act_1234567890'
                                                                                : '123-456-7890'
                                                                        }
                                                                        className={
                                                                            !val.trim()
                                                                                ? 'border-destructive'
                                                                                : ''
                                                                        }
                                                                    />
                                                                    {accountIdList.length >
                                                                        1 && (
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="text-red-600"
                                                                            onClick={() =>
                                                                                setAccountIdList(
                                                                                    accountIdList.filter(
                                                                                        (
                                                                                            _,
                                                                                            i,
                                                                                        ) =>
                                                                                            i !==
                                                                                            idx,
                                                                                    ),
                                                                                )
                                                                            }
                                                                        >
                                                                            <X className="h-4 w-4" />
                                                                        </Button>
                                                                    )}
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        {isApproveMeta
                                                            ? t(
                                                                  'service_orders.form.account_id_hint_meta',
                                                              )
                                                            : t(
                                                                  'service_orders.form.account_id_hint_google',
                                                              )}
                                                    </p>
                                                </div>

                                                {bmId &&
                                                    !loadingBmAccounts &&
                                                    bmAccounts.length === 0 && (
                                                        <p className="text-xs text-orange-500">
                                                            {isApproveMeta
                                                                ? t(
                                                                      'service_orders.form.account_not_found_in_bm',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.account_not_found_in_mcc',
                                                                  )}
                                                        </p>
                                                    )}
                                                <p className="text-xs text-muted-foreground italic">
                                                    {t(
                                                        'service_orders.form.assign_note',
                                                    )}
                                                </p>
                                            </>
                                        )}
                                    </>
                                </div>

                                {Object.keys(formErrors).length > 0 && (
                                    <div className="rounded-md bg-destructive/10 p-3 text-xs text-destructive font-medium space-y-1 my-2 border border-destructive/20">
                                        {Object.entries(formErrors).map(([key, msg]) => (
                                            <div key={key}>• {msg}</div>
                                        ))}
                                    </div>
                                )}

                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => setDialogOpen(false)}
                                    >
                                        {t('common.back')}
                                    </Button>
                                    <Button
                                        onClick={() => {
                                            handleSubmitApprove(
                                                accountIdList.filter(Boolean),
                                            );
                                        }}
                                        disabled={approveProcessing}
                                    >
                                        {approveProcessing
                                            ? t('common.processing')
                                            : t('common.confirm')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        <Dialog
                            open={editDialogOpen}
                            onOpenChange={setEditDialogOpen}
                        >
                            <DialogContent className="flex max-h-[90vh] flex-col">
                                <DialogHeader>
                                    <DialogTitle>
                                        {t('service_orders.edit_config_title')}
                                    </DialogTitle>
                                    <DialogDescription>
                                        {t(
                                            'service_orders.edit_config_description',
                                        )}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="flex-1 space-y-4 overflow-y-auto py-2 pr-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="edit_payment_type">
                                            {t('service_purchase.payment_type')}
                                        </Label>
                                        <div className="flex gap-2">
                                            <Button
                                                type="button"
                                                variant={
                                                    editPaymentType === 'prepay'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={
                                                    editPaymentType !== 'prepay'
                                                }
                                                onClick={() =>
                                                    setEditPaymentType('prepay')
                                                }
                                            >
                                                {t(
                                                    'service_purchase.payment_prepay',
                                                )}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant={
                                                    editPaymentType ===
                                                    'postpay'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={
                                                    editPaymentType !==
                                                    'postpay'
                                                }
                                                onClick={() =>
                                                    setEditPaymentType(
                                                        'postpay',
                                                    )
                                                }
                                            >
                                                {t(
                                                    'service_purchase.payment_postpay',
                                                )}
                                            </Button>
                                        </div>
                                    </div>

                                    {editUseAccountsStructure ? (
                                        <>
                                            <div className="flex items-center justify-between">
                                                <Label className="text-base font-semibold">
                                                    {isEditMeta
                                                        ? t(
                                                              'service_purchase.meta_account_info',
                                                              {
                                                                  defaultValue:
                                                                      'Thông tin tài khoản Meta',
                                                              },
                                                          )
                                                        : t(
                                                              'service_purchase.google_account_info',
                                                              {
                                                                  defaultValue:
                                                                      'Thông tin tài khoản Google',
                                                              },
                                                          )}
                                                </Label>
                                                {editAccounts.length < 999 && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            setEditAccounts([
                                                                ...editAccounts,
                                                                {
                                                                    meta_email:
                                                                        '',
                                                                    display_name:
                                                                        '',
                                                                    bm_ids: [],
                                                                    fanpages:
                                                                        isEditMeta
                                                                            ? []
                                                                            : [],
                                                                    websites:
                                                                        [],
                                                                    timezone_bm:
                                                                        '',
                                                                    asset_access:
                                                                        'full_asset',
                                                                },
                                                            ]);
                                                        }}
                                                    >
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        {t(
                                                            'service_purchase.add_account',
                                                            {
                                                                defaultValue:
                                                                    'Thêm tài khoản',
                                                            },
                                                        )}
                                                    </Button>
                                                )}
                                            </div>
                                            <div className="max-h-[60vh] space-y-4 overflow-y-auto">
                                                {editAccounts.map(
                                                    (account, idx) => (
                                                        <AccountFormEdit
                                                            key={idx}
                                                            account={account}
                                                            accountIndex={idx}
                                                            platform={
                                                                selectedEditOrder
                                                                    ?.package
                                                                    ?.platform ??
                                                                0
                                                            }
                                                            metaTimezones={
                                                                meta_timezones
                                                            }
                                                            googleTimezones={
                                                                google_timezones
                                                            }
                                                            onUpdate={(
                                                                index,
                                                                data,
                                                            ) => {
                                                                const newAccounts =
                                                                    [
                                                                        ...editAccounts,
                                                                    ];
                                                                newAccounts[
                                                                    index
                                                                ] = data;
                                                                setEditAccounts(
                                                                    newAccounts,
                                                                );
                                                            }}
                                                            onRemove={(
                                                                index,
                                                            ) => {
                                                                setEditAccounts(
                                                                    editAccounts.filter(
                                                                        (
                                                                            _,
                                                                            i,
                                                                        ) =>
                                                                            i !==
                                                                            index,
                                                                    ),
                                                                );
                                                            }}
                                                            canRemove={
                                                                editAccounts.length >
                                                                1
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div className="space-y-2">
                                                <Label htmlFor="edit_meta_email">
                                                    {t(
                                                        'service_purchase.meta_email',
                                                    )}
                                                </Label>
                                                <Input
                                                    id="edit_meta_email"
                                                    type="email"
                                                    value={editMetaEmail || ''}
                                                    onChange={(e) =>
                                                        setEditMetaEmail(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={t(
                                                        'service_orders.form.meta_email_placeholder',
                                                    )}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="edit_display_name">
                                                    {t(
                                                        'service_purchase.display_name',
                                                    )}
                                                </Label>
                                                <Input
                                                    id="edit_display_name"
                                                    value={
                                                        editDisplayName || ''
                                                    }
                                                    onChange={(e) =>
                                                        setEditDisplayName(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={t(
                                                        'service_orders.form.display_name_placeholder',
                                                    )}
                                                />
                                            </div>

                                            {/* Tabs: Gán BM / Gán tài khoản */}
                                            <div className="space-y-2">
                                                <div className="flex gap-1 rounded-lg border p-1">
                                                    <button
                                                        type="button"
                                                        className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                                            editAssignMode ===
                                                            'bm'
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'text-muted-foreground hover:bg-muted'
                                                        }`}
                                                        onClick={() =>
                                                            setEditAssignMode(
                                                                'bm',
                                                            )
                                                        }
                                                    >
                                                        {isEditMeta
                                                            ? t(
                                                                  'service_orders.form.assign_bm',
                                                              )
                                                            : t(
                                                                  'service_orders.form.assign_mcc',
                                                              )}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                                            editAssignMode ===
                                                            'account'
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'text-muted-foreground hover:bg-muted'
                                                        }`}
                                                        onClick={() => {
                                                            setEditAssignMode(
                                                                'account',
                                                            );
                                                            if (editBmId)
                                                                handleEditSelectBmFromList(
                                                                    editBmId,
                                                                );
                                                        }}
                                                    >
                                                        {t(
                                                            'service_orders.form.assign_account',
                                                        )}
                                                    </button>
                                                </div>
                                            </div>

                                            {editAssignMode === 'bm' ? (
                                                <>
                                                    {/* ==== TAB GÁN BM ==== */}

                                                    {/* Dropdown chọn BM có sẵn */}
                                                    <div className="space-y-2">
                                                        <Label>
                                                            {isEditMeta
                                                                ? t(
                                                                      'service_orders.form.select_bm_available',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.select_mcc_available',
                                                                  )}
                                                        </Label>
                                                        <SearchableSelect
                                                            key={`edit-bm-tab-bm-${editBmIdList.join(',')}`}
                                                            options={editBmList
                                                                .filter(
                                                                    (bm) =>
                                                                        !editBmIdList.some(
                                                                            (
                                                                                id,
                                                                            ) =>
                                                                                id.trim() ===
                                                                                (bm
                                                                                    .bm_ids?.[0] ||
                                                                                    bm.id),
                                                                        ),
                                                                )
                                                                .map((bm) => ({
                                                                    value:
                                                                        bm
                                                                            .bm_ids?.[0] ||
                                                                        bm.id,
                                                                    label: `${bm.bm_name || bm.name} (${bm.bm_ids?.[0] || bm.id})`,
                                                                    sublabel:
                                                                        bm
                                                                            .bm_ids?.[0] ||
                                                                        bm.id,
                                                                }))}
                                                            value=""
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                if (
                                                                    value &&
                                                                    value !==
                                                                        '__empty__'
                                                                ) {
                                                                    handleEditSelectBmFromList(
                                                                        value,
                                                                    );
                                                                    addToListUnique(
                                                                        editBmIdList,
                                                                        setEditBmIdList,
                                                                        value,
                                                                    );
                                                                }
                                                            }}
                                                            placeholder={
                                                                editLoadingBmList
                                                                    ? t(
                                                                          'service_orders.form.loading_child_bms',
                                                                      )
                                                                    : isEditMeta
                                                                      ? t(
                                                                            'service_orders.form.select_bm_from_list',
                                                                        )
                                                                      : t(
                                                                            'service_orders.form.select_mcc_from_list',
                                                                        )
                                                            }
                                                            searchPlaceholder={
                                                                isEditMeta
                                                                    ? t(
                                                                          'service_orders.form.filter_bm_placeholder',
                                                                          {
                                                                              defaultValue:
                                                                                  'Lọc danh sách BM...',
                                                                          },
                                                                      )
                                                                    : t(
                                                                          'service_orders.form.filter_mcc_placeholder',
                                                                          {
                                                                              defaultValue:
                                                                                  'Lọc danh sách MCC...',
                                                                          },
                                                                      )
                                                            }
                                                            disabled={
                                                                editLoadingBmList
                                                            }
                                                        />
                                                    </div>

                                                    {/* Input ID BM nhập tay + nút thêm */}
                                                    <div className="space-y-2">
                                                        <div className="flex items-center justify-between">
                                                            <Label>
                                                                {isEditMeta
                                                                    ? t(
                                                                          'service_orders.form.bm_id_label',
                                                                      )
                                                                    : t(
                                                                          'service_orders.form.mcc_id_label',
                                                                      )}
                                                            </Label>
                                                            {editBmIdList.length <
                                                                999 && (
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="h-7 text-xs"
                                                                    onClick={() =>
                                                                        setEditBmIdList(
                                                                            [
                                                                                ...editBmIdList,
                                                                                '',
                                                                            ],
                                                                        )
                                                                    }
                                                                >
                                                                    <Plus className="mr-1 h-3 w-3" />
                                                                    {isEditMeta
                                                                        ? t(
                                                                              'service_orders.form.add_bm',
                                                                          )
                                                                        : t(
                                                                              'service_orders.form.add_mcc',
                                                                          )}
                                                                </Button>
                                                            )}
                                                        </div>
                                                        <div className="space-y-2">
                                                            {editBmIdList.map(
                                                                (val, idx) => (
                                                                    <div
                                                                        key={`edit-bm-${idx}`}
                                                                        className="flex gap-2"
                                                                    >
                                                                        <Input
                                                                            value={
                                                                                val
                                                                            }
                                                                            onChange={(
                                                                                e,
                                                                            ) => {
                                                                                const newList =
                                                                                    [
                                                                                        ...editBmIdList,
                                                                                    ];
                                                                                newList[
                                                                                    idx
                                                                                ] =
                                                                                    e.target.value;
                                                                                setEditBmIdList(
                                                                                    newList,
                                                                                );
                                                                                if (
                                                                                    idx ===
                                                                                    0
                                                                                )
                                                                                    setEditBmId(
                                                                                        e
                                                                                            .target
                                                                                            .value,
                                                                                    );
                                                                            }}
                                                                            placeholder={
                                                                                isEditMeta
                                                                                    ? t(
                                                                                          'service_orders.form.enter_bm_id',
                                                                                      )
                                                                                    : t(
                                                                                          'service_orders.form.enter_mcc_id',
                                                                                      )
                                                                            }
                                                                        />
                                                                        {editBmIdList.length >
                                                                            1 && (
                                                                            <Button
                                                                                type="button"
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                className="text-red-600"
                                                                                onClick={() =>
                                                                                    setEditBmIdList(
                                                                                        editBmIdList.filter(
                                                                                            (
                                                                                                _,
                                                                                                i,
                                                                                            ) =>
                                                                                                i !==
                                                                                                idx,
                                                                                        ),
                                                                                    )
                                                                                }
                                                                            >
                                                                                <X className="h-4 w-4" />
                                                                            </Button>
                                                                        )}
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                </>
                                            ) : (
                                                <>
                                                    {/* ==== TAB GÁN TÀI KHOẢN ==== */}

                                                    {/* 1. Dropdown chọn BM có sẵn */}
                                                    <div className="space-y-2">
                                                        <Label htmlFor="edit_select_bm_from_list_account">
                                                            {isEditMeta
                                                                ? t(
                                                                      'service_orders.form.select_bm_available',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.select_mcc_available',
                                                                  )}
                                                        </Label>
                                                        <SearchableSelect
                                                            key={`edit-account-tab-bm-${editBmId}`}
                                                            options={editBmList.map(
                                                                (bm) => ({
                                                                    value:
                                                                        bm
                                                                            .bm_ids?.[0] ||
                                                                        bm.id,
                                                                    label: `${bm.bm_name || bm.name} (${bm.bm_ids?.[0] || bm.id})`,
                                                                    sublabel:
                                                                        bm
                                                                            .bm_ids?.[0] ||
                                                                        bm.id,
                                                                }),
                                                            )}
                                                            value={
                                                                editBmId || ''
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                if (
                                                                    value &&
                                                                    value !==
                                                                        '__empty__'
                                                                ) {
                                                                    handleEditSelectBmFromList(
                                                                        value,
                                                                    );
                                                                    addToListUnique(
                                                                        editBmIdList,
                                                                        setEditBmIdList,
                                                                        value,
                                                                    );
                                                                }
                                                            }}
                                                            placeholder={
                                                                editLoadingBmList
                                                                    ? t(
                                                                          'service_orders.form.loading_child_bms',
                                                                      )
                                                                    : isEditMeta
                                                                      ? t(
                                                                            'service_orders.form.select_bm_from_list',
                                                                        )
                                                                      : t(
                                                                            'service_orders.form.select_mcc_from_list',
                                                                        )
                                                            }
                                                            searchPlaceholder={
                                                                isEditMeta
                                                                    ? t(
                                                                          'service_orders.form.filter_bm_placeholder',
                                                                          {
                                                                              defaultValue:
                                                                                  'Lọc danh sách BM...',
                                                                          },
                                                                      )
                                                                    : t(
                                                                          'service_orders.form.filter_mcc_placeholder',
                                                                          {
                                                                              defaultValue:
                                                                                  'Lọc danh sách MCC...',
                                                                          },
                                                                      )
                                                            }
                                                            disabled={
                                                                editLoadingBmList
                                                            }
                                                        />
                                                    </div>

                                                    {/* 2. Dropdown chọn tài khoản có sẵn */}
                                                    <div className="space-y-2">
                                                        <Label
                                                            htmlFor="edit_select_account_from_list"
                                                            className="text-destructive"
                                                        >
                                                            {t(
                                                                'service_orders.form.select_account_label',
                                                            )}{' '}
                                                            *
                                                        </Label>
                                                        <SearchableSelect
                                                            options={editBmAccounts.map(
                                                                (acc: any) => {
                                                                    const alreadyInList =
                                                                        editAccountIdList.some(
                                                                            (
                                                                                id,
                                                                            ) =>
                                                                                id.trim() ===
                                                                                acc.account_id,
                                                                        );
                                                                    const alreadyAssigned =
                                                                        !!acc.service_user_id;
                                                                    let suffix =
                                                                        '';
                                                                    if (
                                                                        alreadyInList
                                                                    ) {
                                                                        suffix =
                                                                            ' [Đã chọn]';
                                                                    } else if (
                                                                        alreadyAssigned
                                                                    ) {
                                                                        suffix =
                                                                            ' [Đã gán KH khác]';
                                                                    } else {
                                                                        suffix =
                                                                            ' [Chưa gán]';
                                                                    }
                                                                    return {
                                                                        value: acc.account_id,
                                                                        label: `${acc.account_name || acc.account_id} — ${acc.account_id} (${acc.currency})${suffix}`,
                                                                        sublabel:
                                                                            acc.account_id,
                                                                        disabled:
                                                                            alreadyInList,
                                                                    };
                                                                },
                                                            )}
                                                            value=""
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                if (
                                                                    value &&
                                                                    value !==
                                                                        '__empty__'
                                                                ) {
                                                                    addToListUnique(
                                                                        editAccountIdList,
                                                                        setEditAccountIdList,
                                                                        value,
                                                                    );
                                                                    setEditAccountIdInput(
                                                                        value,
                                                                    );
                                                                }
                                                            }}
                                                            placeholder={
                                                                !editBmId
                                                                    ? t(
                                                                          'service_orders.form.select_bm_first',
                                                                      )
                                                                    : editLoadingBmAccounts
                                                                      ? t(
                                                                            'service_orders.form.loading_child_bms',
                                                                        )
                                                                      : t(
                                                                            'service_orders.form.select_account_in_bm_mcc',
                                                                        )
                                                            }
                                                            searchPlaceholder={t(
                                                                'service_orders.form.search_account_placeholder',
                                                                {
                                                                    defaultValue:
                                                                        'Tìm kiếm tài khoản...',
                                                                },
                                                            )}
                                                            disabled={
                                                                editLoadingBmAccounts ||
                                                                !editBmId
                                                            }
                                                        />
                                                    </div>

                                                    {/* 3. Input ID tài khoản nhập tay + nút thêm */}
                                                    <div className="space-y-2">
                                                        <div className="flex items-center justify-between">
                                                            <Label className="text-destructive">
                                                                {t(
                                                                    'service_orders.form.account_id_label',
                                                                )}{' '}
                                                                *
                                                            </Label>
                                                            {editAccountIdList.length <
                                                                999 && (
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="h-7 text-xs"
                                                                    onClick={() =>
                                                                        setEditAccountIdList(
                                                                            [
                                                                                ...editAccountIdList,
                                                                                '',
                                                                            ],
                                                                        )
                                                                    }
                                                                >
                                                                    <Plus className="mr-1 h-3 w-3" />
                                                                    {t(
                                                                        'service_orders.form.add_account',
                                                                    )}
                                                                </Button>
                                                            )}
                                                        </div>
                                                        <div className="space-y-2">
                                                            {editAccountIdList.map(
                                                                (val, idx) => (
                                                                    <div
                                                                        key={`edit-acc-${idx}`}
                                                                        className="flex gap-2"
                                                                    >
                                                                        <Input
                                                                            value={
                                                                                val
                                                                            }
                                                                            onChange={(
                                                                                e,
                                                                            ) => {
                                                                                const newList =
                                                                                    [
                                                                                        ...editAccountIdList,
                                                                                    ];
                                                                                newList[
                                                                                    idx
                                                                                ] =
                                                                                    e.target.value;
                                                                                setEditAccountIdList(
                                                                                    newList,
                                                                                );
                                                                                setEditAccountIdInput(
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                );
                                                                            }}
                                                                            placeholder={
                                                                                isEditMeta
                                                                                    ? 'act_1234567890'
                                                                                    : '123-456-7890'
                                                                            }
                                                                            className={
                                                                                !val.trim()
                                                                                    ? 'border-destructive'
                                                                                    : ''
                                                                            }
                                                                        />
                                                                        {editAccountIdList.length >
                                                                            1 && (
                                                                            <Button
                                                                                type="button"
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                className="text-red-600"
                                                                                onClick={() =>
                                                                                    setEditAccountIdList(
                                                                                        editAccountIdList.filter(
                                                                                            (
                                                                                                _,
                                                                                                i,
                                                                                            ) =>
                                                                                                i !==
                                                                                                idx,
                                                                                        ),
                                                                                    )
                                                                                }
                                                                            >
                                                                                <X className="h-4 w-4" />
                                                                            </Button>
                                                                        )}
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                        <p className="text-xs text-muted-foreground">
                                                            {isEditMeta
                                                                ? t(
                                                                      'service_orders.form.account_id_hint_meta',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.account_id_hint_google',
                                                                  )}
                                                        </p>
                                                    </div>

                                                    {/* 4. Input ID BM khách nhập */}
                                                    <div className="space-y-2">
                                                        <Label>
                                                            {isEditMeta
                                                                ? t(
                                                                      'service_orders.form.bm_id_customer_input',
                                                                  )
                                                                : t(
                                                                      'service_orders.form.mcc_id_customer_input',
                                                                  )}
                                                        </Label>
                                                        <Input
                                                            value={editBmId}
                                                            onChange={(e) => {
                                                                setEditBmId(
                                                                    e.target
                                                                        .value,
                                                                );
                                                                setEditAccountIdInput(
                                                                    '',
                                                                );
                                                            }}
                                                            placeholder={
                                                                isEditMeta
                                                                    ? t(
                                                                          'service_orders.form.bm_id_customer_placeholder',
                                                                      )
                                                                    : t(
                                                                          'service_orders.form.mcc_id_customer_placeholder',
                                                                      )
                                                            }
                                                        />
                                                    </div>
                                                </>
                                            )}

                                            {isEditMeta && (
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between">
                                                        <Label>
                                                            {t(
                                                                'service_orders.form.info_fanpage',
                                                            )}
                                                        </Label>
                                                        {editFanpageList.length <
                                                            999 && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-7 text-xs"
                                                                onClick={() =>
                                                                    setEditFanpageList(
                                                                        [
                                                                            ...editFanpageList,
                                                                            '',
                                                                        ],
                                                                    )
                                                                }
                                                            >
                                                                <Plus className="mr-1 h-3 w-3" />
                                                                {t(
                                                                    'service_orders.form.add_fanpage',
                                                                )}
                                                            </Button>
                                                        )}
                                                    </div>
                                                    <div className="space-y-2">
                                                        {editFanpageList.map(
                                                            (val, idx) => (
                                                                <div
                                                                    key={`edit-fp-${idx}`}
                                                                    className="flex gap-2"
                                                                >
                                                                    <Input
                                                                        value={
                                                                            val
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            const newList =
                                                                                [
                                                                                    ...editFanpageList,
                                                                                ];
                                                                            newList[
                                                                                idx
                                                                            ] =
                                                                                e.target.value;
                                                                            setEditFanpageList(
                                                                                newList,
                                                                            );
                                                                            if (
                                                                                idx ===
                                                                                0
                                                                            ) {
                                                                                setEditInfoFanpage(
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                );
                                                                            }
                                                                        }}
                                                                        placeholder={t(
                                                                            'service_orders.form.info_fanpage_placeholder',
                                                                        )}
                                                                    />
                                                                    {editFanpageList.length >
                                                                        1 && (
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="text-red-600"
                                                                            onClick={() =>
                                                                                setEditFanpageList(
                                                                                    editFanpageList.filter(
                                                                                        (
                                                                                            _,
                                                                                            i,
                                                                                        ) =>
                                                                                            i !==
                                                                                            idx,
                                                                                    ),
                                                                                )
                                                                            }
                                                                        >
                                                                            <X className="h-4 w-4" />
                                                                        </Button>
                                                                    )}
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            )}

                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <Label>
                                                        {t(
                                                            'service_orders.form.info_website',
                                                        )}
                                                    </Label>
                                                    {editWebsiteList.length <
                                                        999 && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-7 text-xs"
                                                            onClick={() =>
                                                                setEditWebsiteList(
                                                                    [
                                                                        ...editWebsiteList,
                                                                        '',
                                                                    ],
                                                                )
                                                            }
                                                        >
                                                            <Plus className="mr-1 h-3 w-3" />
                                                            {t(
                                                                'service_orders.form.add_website',
                                                            )}
                                                        </Button>
                                                    )}
                                                </div>
                                                <div className="space-y-2">
                                                    {editWebsiteList.map(
                                                        (val, idx) => (
                                                            <div
                                                                key={`edit-ws-${idx}`}
                                                                className="flex gap-2"
                                                            >
                                                                <Input
                                                                    value={val}
                                                                    onChange={(
                                                                        e,
                                                                    ) => {
                                                                        const newList =
                                                                            [
                                                                                ...editWebsiteList,
                                                                            ];
                                                                        newList[
                                                                            idx
                                                                        ] =
                                                                            e.target.value;
                                                                        setEditWebsiteList(
                                                                            newList,
                                                                        );
                                                                        if (
                                                                            idx ===
                                                                            0
                                                                        ) {
                                                                            setEditInfoWebsite(
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            );
                                                                        }
                                                                    }}
                                                                    placeholder={t(
                                                                        'service_orders.form.info_website_placeholder',
                                                                    )}
                                                                />
                                                                {editWebsiteList.length >
                                                                    1 && (
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        className="text-red-600"
                                                                        onClick={() =>
                                                                            setEditWebsiteList(
                                                                                editWebsiteList.filter(
                                                                                    (
                                                                                        _,
                                                                                        i,
                                                                                    ) =>
                                                                                        i !==
                                                                                        idx,
                                                                                ),
                                                                            )
                                                                        }
                                                                    >
                                                                        <X className="h-4 w-4" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="edit_asset_access">
                                                    {t(
                                                        'service_purchase.asset_access_label',
                                                    )}
                                                </Label>
                                                <Select
                                                    value={
                                                        editAssetAccess ||
                                                        'full_asset'
                                                    }
                                                    onValueChange={(
                                                        value:
                                                            | 'full_asset'
                                                            | 'basic_asset',
                                                    ) =>
                                                        setEditAssetAccess(
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger id="edit_asset_access">
                                                        <SelectValue
                                                            placeholder={t(
                                                                'service_purchase.asset_access_placeholder',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="full_asset">
                                                            {t(
                                                                'service_purchase.asset_access_full',
                                                            )}
                                                        </SelectItem>
                                                        <SelectItem value="basic_asset">
                                                            {t(
                                                                'service_purchase.asset_access_basic',
                                                            )}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="edit_timezone_bm">
                                                    {isEditMeta
                                                        ? t(
                                                              'service_purchase.timezone_bm_label',
                                                              {
                                                                  defaultValue:
                                                                      'Múi giờ BM',
                                                              },
                                                          )
                                                        : t(
                                                              'service_purchase.timezone_mcc_label',
                                                              {
                                                                  defaultValue:
                                                                      'Múi giờ MCC',
                                                              },
                                                          )}
                                                </Label>
                                                <TimezoneSelect
                                                    id="edit_timezone_bm"
                                                    value={editTimezoneBm || ''}
                                                    onValueChange={(value) =>
                                                        setEditTimezoneBm(value)
                                                    }
                                                    options={
                                                        isEditMeta
                                                            ? meta_timezones
                                                            : google_timezones
                                                    }
                                                    placeholder={t(
                                                        'service_purchase.timezone_bm_placeholder',
                                                        {
                                                            defaultValue:
                                                                'Chọn múi giờ',
                                                        },
                                                    )}
                                                />
                                            </div>
                                        </>
                                    )}
                                </div>

                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => setEditDialogOpen(false)}
                                    >
                                        {t('common.back')}
                                    </Button>
                                    <Button onClick={handleSubmitUpdate}>
                                        {t('common.save')}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </>
                )}
            </div>
        </AppLayout>
    );
};

export default ServiceOrdersIndex;
