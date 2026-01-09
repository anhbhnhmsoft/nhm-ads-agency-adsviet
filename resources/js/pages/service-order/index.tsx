import { useMemo, useCallback } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import type { ServiceOrderPagination, ServiceOrder } from '@/pages/service-order/types/type';
import { service_purchase_index, service_orders_cancel, service_orders_destroy } from '@/routes';
import { Package, ShoppingBag, Trash2 } from 'lucide-react';
import { DataTable } from '@/components/table/data-table';
import type { ColumnDef } from '@tanstack/react-table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useServiceOrderAdminDialog } from '@/pages/service-order/hooks/use-admin-approve-dialog';
import { useServiceOrderEditConfigDialog } from '@/pages/service-order/hooks/use-edit-config-dialog';
import { AccountInfoCell } from '@/pages/service-order/components/AccountInfoCell';
import { AccountFormEdit } from '@/pages/service-order/components/AccountFormEdit';
import useCheckRole from '@/hooks/use-check-role';
import { _PlatformType, _UserRole } from '@/lib/types/constants';
import { Pencil, Plus } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TimezoneSelect } from '@/components/timezone-select';

type TimezoneOption = {
    value: string;
    label: string;
};

type Props = {
    paginator: ServiceOrderPagination;
    meta_timezones?: TimezoneOption[];
    google_timezones?: TimezoneOption[];
};

const STATUS_COLORS: Record<string, string> = {
    PENDING: 'bg-amber-500 text-white',
    QUEUE_JOB_PENDING: 'bg-[#eb4e23] text-white',
    QUEUE_JOB_ON_PROCESS: 'bg-[#eb4e23] text-white',
    PROCESSING: 'bg-indigo-500 text-white',
    ACTIVE: 'bg-green-500 text-white',
    FAILED: 'bg-red-500 text-white',
    CANCELLED: 'bg-gray-500 text-white',
};

const ServiceOrdersIndex = ({ paginator, meta_timezones = [], google_timezones = [] }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth);
    const is_admin_view = checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE]);
    const orders = paginator?.data ?? [];

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
        infoFanpage,
        setInfoFanpage,
        infoWebsite,
        setInfoWebsite,
        paymentType,
        setPaymentType,
        assetAccess,
        setAssetAccess,
        timezoneBm,
        setTimezoneBm,
        openDialogForOrder,
        handleSubmitApprove,
        formErrors,
        processing: approveProcessing,
    } = useServiceOrderAdminDialog();

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
    } = useServiceOrderEditConfigDialog();

    const isApproveMeta = selectedOrder?.package?.platform === _PlatformType.META;
    const isEditMeta = selectedEditOrder?.package?.platform === _PlatformType.META;

    const getStatusInfo = useCallback(
        (statusLabel?: string | null) => {
            if (!statusLabel) return { label: t('service_orders.status.unknown'), className: 'bg-muted' };
            const className = STATUS_COLORS[statusLabel] || 'bg-muted';
            const label = t(`service_orders.status.${statusLabel.toLowerCase()}`, { defaultValue: statusLabel });
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
                cell: ({ getValue }) => <span className="font-mono text-xs">{String(getValue())}</span>,
            },
            {
                id: 'referral',
                header: t('service_orders.table.referral'),
                meta: { headerClassName: 'text-center', cellClassName: 'text-center' },
                cell: ({ row }) => {
                    const referrerName = row.original.user?.referrer?.name || '';
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
                    headerClassName: 'w-[475px] min-w-[475px] max-w-[475px] break-words whitespace-normal',
                    cellClassName: "w-[475px] min-w-[475px] max-w-[475px] break-words whitespace-normal"  },
                cell: ({ row }) => {
                    return (
                        <AccountInfoCell
                            config={row.original.config_account || null}
                            platform={row.original.package?.platform}
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
                meta: { headerClassName: 'text-right', cellClassName: 'text-right' },
                cell: ({ row }) => {
                    const config = row.original.config_account || {};
                    const paymentType = ((config.payment_type as string) || '').toLowerCase();
                    const topupRaw = config.top_up_amount as number | string | undefined;
                    const isTopupMissing = topupRaw === undefined || topupRaw === null || topupRaw === '';
                    const isPostpay = paymentType === 'postpay' || (paymentType === '' && isTopupMissing);

                    if (isPostpay) {
                        return <span className="text-xs text-muted-foreground">{t('service_orders.table.postpay_label')}</span>;
                    }

                    if (isTopupMissing) {
                        return <span className="text-xs text-muted-foreground">-</span>;
                    }
                    const num = Number(topupRaw);
                    if (Number.isNaN(num)) {
                        return <span className="text-xs text-muted-foreground">-</span>;
                    }
                    return <span className="text-xs font-medium">{num.toFixed(2)} USDT</span>;
                },
            },
            {
                id: 'budget',
                header: t('service_orders.table.budget'),
                meta: { headerClassName: 'text-right', cellClassName: 'text-right' },
                cell: ({ row }) => {
                    const budget = row.original.budget;
                    if (!budget) {
                        return <span className="text-xs text-muted-foreground">{t('service_orders.table.budget_unlimited', { defaultValue: 'Không giới hạn' })}</span>;
                    }
                    const budgetValue = parseFloat(budget);
                    if (Number.isNaN(budgetValue)) {
                        return <span className="text-xs text-muted-foreground">-</span>;
                    }
                    if (budgetValue === 0) {
                        return <span className="text-xs font-medium text-muted-foreground">{t('service_orders.table.budget_unlimited', { defaultValue: 'Không giới hạn' })}</span>;
                    }
                    return <span className="text-xs font-medium">{budgetValue.toFixed(2)} USD</span>;
                },
            },
            {
                id: 'status',
                header: t('service_orders.table.status'),
                meta: { headerClassName: 'text-center', cellClassName: 'text-center' },
                cell: ({ row }) => {
                    const info = getStatusInfo(row.original.status_label);
                    return <Badge className={info.className}>{info.label}</Badge>;
                },
            },
            {
                accessorKey: 'created_at',
                header: t('service_orders.table.created_at'),
                cell: ({ getValue }) => (
                    <span className="text-xs text-muted-foreground">{formatDateTime(getValue() as string | null)}</span>
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
                        if (!window.confirm(t('service_orders.confirm_cancel'))) {
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
                        if (!window.confirm(t('service_orders.confirm_delete'))) {
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
                                    <Button size="sm" variant="default" onClick={handleApprove}>
                                        {t('service_orders.actions.approve')}
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={handleCancel}>
                                        {t('service_orders.actions.cancel')}
                                    </Button>
                                </>
                            )}
                            <Button size="sm" variant="outline" onClick={handleEdit}>
                                <Pencil className="mr-1 h-3 w-3" />
                                {t('service_orders.actions.edit')}
                            </Button>
                            <Button size="sm" variant="outline" onClick={handleDelete} className="text-red-600 hover:text-red-700 hover:bg-red-50">
                                <Trash2 className="mr-1 h-3 w-3" />
                                {t('service_orders.actions.delete')}
                            </Button>
                        </div>
                    );
                },
            });
        }

        return baseColumns;
    }, [t, is_admin_view, openDialogForOrder, openEditDialogForOrder, getStatusInfo]);

    return (
        <AppLayout>
            <Head title={t(is_admin_view ? 'service_orders.admin_title' : 'service_orders.title')} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t(is_admin_view ? 'service_orders.admin_title' : 'service_orders.title')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t(is_admin_view ? 'service_orders.admin_subtitle' : 'service_orders.subtitle')}
                        </p>
                    </div>
                    {!is_admin_view && (
                        <Button asChild>
                            <Link href={service_purchase_index().url}>
                                <ShoppingBag className="mr-2 h-4 w-4" />
                                {t('service_orders.go_to_packages')}
                            </Link>
                        </Button>
                    )}
                </div>

                <Card className="py-0">
                    {orders.length === 0 ? (
                        <CardContent className="py-12 text-center">
                            <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-muted-foreground">{t('service_orders.empty')}</p>
                        </CardContent>
                    ) : (
                        <CardContent className="p-0">
                            <DataTable<ServiceOrder, unknown> columns={columns} paginator={paginator} />
                        </CardContent>
                    )}
                </Card>

                {is_admin_view && (
                    <>
                    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('service_orders.admin_form_title')}</DialogTitle>
                                <DialogDescription>{t('service_orders.admin_form_description')}</DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4 py-2">
                                <div className="space-y-2">
                                    <Label htmlFor="payment_type">{t('service_purchase.payment_type')}</Label>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant={paymentType === 'prepay' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setPaymentType('prepay')}
                                        >
                                            {t('service_purchase.payment_prepay')}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={paymentType === 'postpay' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setPaymentType('postpay')}
                                        >
                                            {t('service_purchase.payment_postpay')}
                                        </Button>
                                    </div>
                                </div>

                                {approveUseAccountsStructure ? (
                                    <>
                                        <div className="flex items-center justify-between">
                                            <Label className="text-base font-semibold">
                                                {isApproveMeta
                                                    ? t('service_purchase.meta_account_info', { defaultValue: 'Thông tin tài khoản Meta' })
                                                    : t('service_purchase.google_account_info', { defaultValue: 'Thông tin tài khoản Google' })}
                                            </Label>
                                            {approveAccounts.length < 3 && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setApproveAccounts([
                                                            ...approveAccounts,
                                                            {
                                                                meta_email: '',
                                                                display_name: '',
                                                                bm_ids: [],
                                                                fanpages: isApproveMeta ? [] : [],
                                                                websites: [],
                                                                timezone_bm: '',
                                                                asset_access: 'full_asset',
                                                            },
                                                        ]);
                                                    }}
                                                >
                                                    <Plus className="h-4 w-4 mr-2" />
                                                    {t('service_purchase.add_account', { defaultValue: 'Thêm tài khoản' })}
                                                </Button>
                                            )}
                                        </div>
                                        <div className="space-y-4 max-h-[60vh] overflow-y-auto">
                                            {approveAccounts.map((account, idx) => (
                                                <AccountFormEdit
                                                    key={idx}
                                                    account={account}
                                                    accountIndex={idx}
                                                    platform={selectedOrder?.package?.platform || 0}
                                                    metaTimezones={meta_timezones}
                                                    googleTimezones={google_timezones}
                                                    onUpdate={(index, data) => {
                                                        const newAccounts = [...approveAccounts];
                                                        const updatedAccount = {
                                                            ...data,
                                                            bm_ids: Array.isArray(data.bm_ids) ? data.bm_ids : (data.bm_ids ? [data.bm_ids] : []),
                                                            fanpages: Array.isArray(data.fanpages) ? data.fanpages : (data.fanpages ? [data.fanpages] : []),
                                                            websites: Array.isArray(data.websites) ? data.websites : (data.websites ? [data.websites] : []),
                                                        };
                                                        newAccounts[index] = updatedAccount;
                                                        setApproveAccounts(newAccounts);
                                                    }}
                                                    onRemove={(index) => {
                                                        setApproveAccounts(approveAccounts.filter((_, i) => i !== index));
                                                    }}
                                                    canRemove={approveAccounts.length > 1}
                                                />
                                            ))}
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="meta_email">{t('service_purchase.meta_email')}</Label>
                                            <Input
                                                id="meta_email"
                                                type="email"
                                                value={metaEmail}
                                                onChange={(e) => setMetaEmail(e.target.value)}
                                                placeholder={t('service_orders.form.meta_email_placeholder')}
                                            />
                                            {formErrors.meta_email && (
                                                <p className="text-xs text-red-500">{formErrors.meta_email}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="display_name">{t('service_purchase.display_name')}</Label>
                                            <Input
                                                id="display_name"
                                                value={displayName}
                                                onChange={(e) => setDisplayName(e.target.value)}
                                                placeholder={t('service_orders.form.display_name_placeholder')}
                                            />
                                            {formErrors.display_name && (
                                                <p className="text-xs text-red-500">{formErrors.display_name}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="bm_id">
                                                {isApproveMeta
                                                    ? t('service_purchase.id_bm', { defaultValue: 'ID BM' })
                                                    : t('service_purchase.id_mcc', { defaultValue: 'ID MCC' })}
                                            </Label>
                                            <Input
                                                id="bm_id"
                                                value={bmId}
                                                onChange={(e) => setBmId(e.target.value)}
                                                placeholder={t('service_orders.form.bm_id_placeholder')}
                                            />
                                            {formErrors.bm_id && (
                                                <p className="text-xs text-red-500">{formErrors.bm_id}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="approve_asset_access">{t('service_purchase.asset_access_label')}</Label>
                                            <Select
                                                value={assetAccess || 'full_asset'}
                                                onValueChange={(value: 'full_asset' | 'basic_asset') => setAssetAccess(value)}
                                            >
                                                <SelectTrigger id="approve_asset_access">
                                                    <SelectValue placeholder={t('service_purchase.asset_access_placeholder')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="full_asset">{t('service_purchase.asset_access_full')}</SelectItem>
                                                    <SelectItem value="basic_asset">{t('service_purchase.asset_access_basic')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="approve_timezone_bm">
                                                {isApproveMeta
                                                    ? t('service_purchase.timezone_bm_label', { defaultValue: 'Múi giờ BM' })
                                                    : t('service_purchase.timezone_mcc_label', { defaultValue: 'Múi giờ MCC' })}
                                            </Label>
                                            <TimezoneSelect
                                                id="approve_timezone_bm"
                                                value={timezoneBm || ''}
                                                onValueChange={(value) => setTimezoneBm(value)}
                                                options={isApproveMeta ? meta_timezones : google_timezones}
                                                placeholder={t('service_purchase.timezone_bm_placeholder', { defaultValue: 'Chọn múi giờ' })}
                                            />
                                        </div>

                                        {isApproveMeta && (
                                            <>
                                                <div className="space-y-2">
                                                    <Label htmlFor="info_fanpage">{t('service_orders.form.info_fanpage')}</Label>
                                                    <Input
                                                        id="info_fanpage"
                                                        value={infoFanpage}
                                                        onChange={(e) => setInfoFanpage(e.target.value)}
                                                        placeholder={t('service_orders.form.info_fanpage_placeholder')}
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="info_website">{t('service_orders.form.info_website')}</Label>
                                                    <Input
                                                        id="info_website"
                                                        value={infoWebsite}
                                                        onChange={(e) => setInfoWebsite(e.target.value)}
                                                        placeholder={t('service_orders.form.info_website_placeholder')}
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </>
                                )}
                            </div>

                            <DialogFooter>
                                <Button variant="outline" onClick={() => setDialogOpen(false)}>
                                    {t('common.back')}
                                </Button>
                                <Button onClick={() => {
                                    handleSubmitApprove();
                                }} disabled={approveProcessing}>
                                    {approveProcessing ? t('common.processing') : t('common.confirm')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('service_orders.edit_config_title')}</DialogTitle>
                                <DialogDescription>{t('service_orders.edit_config_description')}</DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4 py-2">
                                <div className="space-y-2">
                                    <Label htmlFor="edit_payment_type">{t('service_purchase.payment_type')}</Label>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant={editPaymentType === 'prepay' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setEditPaymentType('prepay')}
                                        >
                                            {t('service_purchase.payment_prepay')}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={editPaymentType === 'postpay' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setEditPaymentType('postpay')}
                                        >
                                            {t('service_purchase.payment_postpay')}
                                        </Button>
                                    </div>
                                </div>
                                {editUseAccountsStructure ? (
                                    <>
                                        <div className="flex items-center justify-between">
                                            <Label className="text-base font-semibold">
                                                {isEditMeta
                                                    ? t('service_purchase.meta_account_info', { defaultValue: 'Thông tin tài khoản Meta' })
                                                    : t('service_purchase.google_account_info', { defaultValue: 'Thông tin tài khoản Google' })}
                                            </Label>
                                            {editAccounts.length < 3 && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setEditAccounts([
                                                            ...editAccounts,
                                                            {
                                                                meta_email: '',
                                                                display_name: '',
                                                                bm_ids: [],
                                                                fanpages: isEditMeta ? [] : [],
                                                                websites: [],
                                                                timezone_bm: '',
                                                                asset_access: 'full_asset',
                                                            },
                                                        ]);
                                                    }}
                                                >
                                                    <Plus className="h-4 w-4 mr-2" />
                                                    {t('service_purchase.add_account', { defaultValue: 'Thêm tài khoản' })}
                                                </Button>
                                            )}
                                        </div>
                                        <div className="space-y-4 max-h-[60vh] overflow-y-auto">
                                            {editAccounts.map((account, idx) => (
                                                <AccountFormEdit
                                                    key={idx}
                                                    account={account}
                                                    accountIndex={idx}
                                                    platform={selectedEditOrder?.package?.platform ?? 0}
                                                    metaTimezones={meta_timezones}
                                                    googleTimezones={google_timezones}
                                                    onUpdate={(index, data) => {
                                                        const newAccounts = [...editAccounts];
                                                        newAccounts[index] = data;
                                                        setEditAccounts(newAccounts);
                                                    }}
                                                    onRemove={(index) => {
                                                        setEditAccounts(editAccounts.filter((_, i) => i !== index));
                                                    }}
                                                    canRemove={editAccounts.length > 1}
                                                />
                                            ))}
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="edit_meta_email">{t('service_purchase.meta_email')}</Label>
                                            <Input
                                                id="edit_meta_email"
                                                type="email"
                                                value={editMetaEmail || ''}
                                                onChange={(e) => setEditMetaEmail(e.target.value)}
                                                placeholder={t('service_orders.form.meta_email_placeholder')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="edit_display_name">{t('service_purchase.display_name')}</Label>
                                            <Input
                                                id="edit_display_name"
                                                value={editDisplayName || ''}
                                                onChange={(e) => setEditDisplayName(e.target.value)}
                                                placeholder={t('service_orders.form.display_name_placeholder')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="edit_bm_id">
                                                {isEditMeta
                                                    ? t('service_purchase.id_bm', { defaultValue: 'ID BM' })
                                                    : t('service_purchase.id_mcc', { defaultValue: 'ID MCC' })}
                                            </Label>
                                            <Input
                                                id="edit_bm_id"
                                                value={editBmId || ''}
                                                onChange={(e) => setEditBmId(e.target.value)}
                                                placeholder={t('service_orders.form.bm_id_placeholder')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="edit_asset_access">{t('service_purchase.asset_access_label')}</Label>
                                            <Select
                                                value={editAssetAccess ?? 'full_asset'}
                                                onValueChange={(value: 'full_asset' | 'basic_asset') => setEditAssetAccess(value)}
                                            >
                                                <SelectTrigger id="edit_asset_access">
                                                    <SelectValue placeholder={t('service_purchase.asset_access_placeholder')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="full_asset">{t('service_purchase.asset_access_full')}</SelectItem>
                                                    <SelectItem value="basic_asset">{t('service_purchase.asset_access_basic')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="edit_timezone_bm">
                                                {isEditMeta
                                                    ? t('service_purchase.timezone_bm_label', { defaultValue: 'Múi giờ BM' })
                                                    : t('service_purchase.timezone_mcc_label', { defaultValue: 'Múi giờ MCC' })}
                                            </Label>
                                            <TimezoneSelect
                                                id="edit_timezone_bm"
                                                value={editTimezoneBm || ''}
                                                onValueChange={(value) => setEditTimezoneBm(value)}
                                                options={isEditMeta ? meta_timezones : google_timezones}
                                                placeholder={t('service_purchase.timezone_bm_placeholder', { defaultValue: 'Chọn múi giờ' })}
                                            />
                                        </div>
                                        {isEditMeta && (
                                            <>
                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_info_fanpage">{t('service_orders.form.info_fanpage')}</Label>
                                                    <Input
                                                        id="edit_info_fanpage"
                                                        value={editInfoFanpage || ''}
                                                        onChange={(e) => setEditInfoFanpage(e.target.value)}
                                                        placeholder={t('service_orders.form.info_fanpage_placeholder')}
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_info_website">{t('service_orders.form.info_website')}</Label>
                                                    <Input
                                                        id="edit_info_website"
                                                        value={editInfoWebsite || ''}
                                                        onChange={(e) => setEditInfoWebsite(e.target.value)}
                                                        placeholder={t('service_orders.form.info_website_placeholder')}
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </>
                                )}
                            </div>

                            <DialogFooter>
                                <Button variant="outline" onClick={() => setEditDialogOpen(false)}>
                                    {t('common.back')}
                                </Button>
                                <Button onClick={handleSubmitUpdate}>{t('common.save')}</Button>
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

