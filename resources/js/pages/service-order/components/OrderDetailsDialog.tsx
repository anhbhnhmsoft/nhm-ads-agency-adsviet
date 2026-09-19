import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { _PlatformType } from '@/lib/types/constants';
import { cn } from '@/lib/utils';
import type {
    AccountConfig,
    AssignedAccount,
    ServiceOrder,
} from '@/pages/service-order/types/type';
import {
    AlertCircle,
    CheckCircle2,
    Check,
    Clock,
    Copy,
    CreditCard,
    ExternalLink,
    Globe,
    Layers,
    Mail,
    Package,
    Share2,
    Shield,
    User,
    Wallet,
} from 'lucide-react';
import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface OrderDetailsDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    order: ServiceOrder | null;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
    getStatusInfo: (statusLabel?: string | null) => {
        label: string;
        className: string;
    };
    formatDateTime: (dateString: string | null) => string;
}

export const OrderDetailsDialog: React.FC<OrderDetailsDialogProps> = ({
    open,
    onOpenChange,
    order,
    metaTimezones = [],
    googleTimezones = [],
    getStatusInfo,
    formatDateTime,
}) => {
    const { t } = useTranslation();
    const [copiedKey, setCopiedKey] = useState<string | null>(null);

    if (!order) return null;

    const handleCopy = (text: string, key: string) => {
        navigator.clipboard.writeText(text);
        setCopiedKey(key);
        setTimeout(() => setCopiedKey(null), 2000);
    };

    const isMeta = order.package?.platform === _PlatformType.META;
    const isGoogle = order.package?.platform === _PlatformType.GOOGLE;
    const statusInfo = getStatusInfo(order.status_label);
    const config = order.config_account || {};
    const billingSource =
        order.package?.billing_source ||
        config.billing_source ||
        (config as any)?.payment_source ||
        '';

    const resolveTimezoneLabel = (value?: string | null): string => {
        if (!value) return '-';
        const options = isMeta ? metaTimezones : googleTimezones;
        const found = options.find((opt) => opt.value === value);
        return found ? found.label : value;
    };

    const getBillingSourceLabel = (src: string) => {
        if (src === 'customer_card') return 'Thẻ của khách (Customer Card)';
        if (src === 'adviet_card') return 'Thẻ Adviet (Adviet Card)';
        if (src === 'supplier_credit_line') return 'Hạn mức nhà cung cấp';
        return src || '-';
    };

    const getMetaAdsManagerUrl = (accountId: string) => {
        const cleanId = accountId.replace(/^act_/, '');
        return `https://adsmanager.facebook.com/adsmanager/manage/campaigns?act=${cleanId}`;
    };

    const assignedAccounts: AssignedAccount[] =
        order.assigned_accounts && order.assigned_accounts.length > 0
            ? order.assigned_accounts
            : (config.resolved_account_ids || []).map((accId, idx) => ({
                  id: String(idx),
                  account_id: accId,
                  business_manager_id: config.resolved_bm_ids?.[idx] || config.bm_id || config.child_bm_id,
              }));

    const rawAccounts: AccountConfig[] = Array.isArray(config.accounts) && config.accounts.length > 0
        ? config.accounts
        : [];

    const isLowBalance = (order.wallet_balance ?? 0) < 100;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-4xl p-0">
                <DialogHeader className="border-b px-6 py-4">
                    <div className="flex flex-wrap items-center justify-between gap-3 pr-6">
                        <div>
                            <div className="flex items-center gap-2">
                                <DialogTitle className="text-lg font-bold">
                                    {t('service_orders.dialog.details_title', {
                                        defaultValue: 'Chi tiết đơn dịch vụ',
                                    })}
                                </DialogTitle>
                                <div className="flex items-center gap-1.5 rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-semibold">
                                    <span>#{order.id}</span>
                                    <button
                                        type="button"
                                        onClick={() => handleCopy(order.id, 'order_id')}
                                        className="text-muted-foreground hover:text-foreground"
                                        title="Copy Order ID"
                                    >
                                        {copiedKey === 'order_id' ? (
                                            <Check className="h-3.5 w-3.5 text-emerald-500" />
                                        ) : (
                                            <Copy className="h-3.5 w-3.5" />
                                        )}
                                    </button>
                                </div>
                            </div>
                            <DialogDescription className="mt-1 text-xs">
                                {t('service_orders.table.created_at')}:{' '}
                                <span className="font-medium text-foreground">
                                    {formatDateTime(order.created_at || null)}
                                </span>
                            </DialogDescription>
                        </div>

                        <div className="flex items-center gap-2">
                            <Badge className={statusInfo.className}>
                                {statusInfo.label}
                            </Badge>
                            {order.billing_health?.status === 'low_balance' && (
                                <Badge variant="destructive" className="bg-red-500 text-white">
                                    {t('service_orders.billing.low_balance')}
                                </Badge>
                            )}
                            {order.billing_health?.status === 'ready_to_charge' && (
                                <Badge className="bg-blue-500 text-white">
                                    {t('service_orders.billing.ready_to_charge')}
                                </Badge>
                            )}
                            {order.billing_health?.status === 'healthy' && (
                                <Badge className="bg-emerald-500 text-white">
                                    {t('service_orders.billing.healthy')}
                                </Badge>
                            )}
                            {order.billing_health?.status === 'prepay' && (
                                <Badge variant="secondary">
                                    {t('service_orders.billing.prepay')}
                                </Badge>
                            )}
                        </div>
                    </div>
                </DialogHeader>

                <ScrollArea className="max-h-[calc(90vh-140px)] px-6 py-4">
                    <div className="space-y-6">
                        {/* 1. Thông tin chung & Khách hàng */}
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="mb-3 flex items-center gap-2 font-semibold text-foreground">
                                    <User className="h-4 w-4 text-blue-500" />
                                    <span>Thông tin khách hàng</span>
                                </div>
                                <div className="space-y-2 text-xs">
                                    <div className="flex justify-between border-b pb-1.5">
                                        <span className="text-muted-foreground">Khách hàng:</span>
                                        <span className="font-medium">{order.user?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between border-b pb-1.5">
                                        <span className="text-muted-foreground">Người giới thiệu:</span>
                                        <span className="font-medium">{order.user?.referrer?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between pb-1">
                                        <span className="text-muted-foreground">Ghi chú đơn hàng:</span>
                                        <span className="font-medium">{order.description || '-'}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="mb-3 flex items-center gap-2 font-semibold text-foreground">
                                    <Package className="h-4 w-4 text-emerald-500" />
                                    <span>Gói dịch vụ & Cấu hình</span>
                                </div>
                                <div className="space-y-2 text-xs">
                                    <div className="flex justify-between border-b pb-1.5">
                                        <span className="text-muted-foreground">Gói dịch vụ:</span>
                                        <span className="font-medium">{order.package?.name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between border-b pb-1.5">
                                        <span className="text-muted-foreground">Nền tảng:</span>
                                        <span className="font-medium">{order.package?.platform_label || '-'}</span>
                                    </div>
                                    <div className="flex justify-between border-b pb-1.5">
                                        <span className="text-muted-foreground">Nguồn thanh toán:</span>
                                        <span className="font-semibold text-indigo-600 dark:text-indigo-400">
                                            {getBillingSourceLabel(billingSource)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between pb-1">
                                        <span className="text-muted-foreground">Hình thức:</span>
                                        <span className="font-medium uppercase">
                                            {order.package?.payment_type || 'PREPAY'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* 2. Tài chính & Số dư ví */}
                        <div className="rounded-lg border bg-card p-4 shadow-sm">
                            <div className="mb-3 flex items-center gap-2 font-semibold text-foreground">
                                <Wallet className="h-4 w-4 text-amber-500" />
                                <span>Tài chính & Số dư ví</span>
                            </div>
                            <div className="grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                                <div className="rounded-md bg-muted/50 p-3">
                                    <span className="text-muted-foreground">Số dư ví khách:</span>
                                    <div
                                        className={cn(
                                            'mt-1 text-sm font-bold',
                                            isLowBalance ? 'text-red-500' : 'text-emerald-600',
                                        )}
                                    >
                                        {Number(order.wallet_balance ?? 0).toFixed(2)} USD
                                    </div>
                                </div>
                                <div className="rounded-md bg-muted/50 p-3">
                                    <span className="text-muted-foreground">Tổng chi tiêu ads:</span>
                                    <div className="mt-1 text-sm font-bold text-foreground">
                                        {Number(order.total_spend ?? 0).toFixed(2)} USD
                                    </div>
                                </div>
                                <div className="rounded-md bg-muted/50 p-3">
                                    <span className="text-muted-foreground">Chi tiêu chưa thu phí:</span>
                                    <div className="mt-1 text-sm font-bold text-amber-600 dark:text-amber-400">
                                        {Number(order.unbilled_spend ?? 0).toFixed(2)} USD
                                    </div>
                                </div>
                                <div className="rounded-md bg-muted/50 p-3">
                                    <span className="text-muted-foreground">Phí dịch vụ chưa thu:</span>
                                    <div className="mt-1 text-sm font-bold text-red-500">
                                        ~{Number(order.pending_fee ?? 0).toFixed(2)} USD
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* 3. Tài khoản quảng cáo ĐÃ GÁN THỰC TẾ (Assigned Accounts) */}
                        <div className="rounded-lg border bg-card p-4 shadow-sm">
                            <div className="mb-3 flex items-center justify-between">
                                <div className="flex items-center gap-2 font-semibold text-foreground">
                                    <CreditCard className="h-4 w-4 text-purple-500" />
                                    <span>Tài khoản quảng cáo đã gán ({assignedAccounts.length})</span>
                                </div>
                            </div>

                            {assignedAccounts.length === 0 ? (
                                <div className="rounded-md bg-muted/40 p-4 text-center text-xs text-muted-foreground">
                                    Đơn hàng này chưa được gán tài khoản quảng cáo thực tế.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {assignedAccounts.map((acc, index) => {
                                        const cleanActId = (acc.account_id || '').replace(/^act_/, '');
                                        return (
                                            <div
                                                key={acc.id || index}
                                                className="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-900/50"
                                            >
                                                <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-2.5">
                                                    <div className="flex items-center gap-2">
                                                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                                                            {index + 1}
                                                        </span>
                                                        <span className="font-semibold text-xs text-foreground">
                                                            {acc.account_name || 'Tài khoản quảng cáo'}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {isMeta && acc.account_id && (
                                                            <a
                                                                href={getMetaAdsManagerUrl(acc.account_id)}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:underline"
                                                            >
                                                                <span>Ads Manager</span>
                                                                <ExternalLink className="h-3 w-3" />
                                                            </a>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="mt-2.5 grid grid-cols-1 gap-2.5 text-xs sm:grid-cols-2 md:grid-cols-3">
                                                    <div>
                                                        <span className="text-muted-foreground">Account ID:</span>
                                                        <div className="mt-0.5 flex items-center gap-1 font-mono font-semibold text-foreground">
                                                            <span>{acc.account_id || '-'}</span>
                                                            {acc.account_id && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        handleCopy(acc.account_id, `acc_${index}`)
                                                                    }
                                                                    className="text-muted-foreground hover:text-foreground"
                                                                    title="Copy Account ID"
                                                                >
                                                                    {copiedKey === `acc_${index}` ? (
                                                                        <Check className="h-3 w-3 text-emerald-500" />
                                                                    ) : (
                                                                        <Copy className="h-3 w-3" />
                                                                    )}
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <span className="text-muted-foreground">
                                                            {isMeta ? 'BM ID:' : 'MCC ID:'}
                                                        </span>
                                                        <div className="mt-0.5 flex items-center gap-1 font-mono text-foreground">
                                                            <span>{acc.business_manager_id || '-'}</span>
                                                            {acc.business_manager_id && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        handleCopy(
                                                                            acc.business_manager_id!,
                                                                            `bm_${index}`,
                                                                        )
                                                                    }
                                                                    className="text-muted-foreground hover:text-foreground"
                                                                    title="Copy BM ID"
                                                                >
                                                                    {copiedKey === `bm_${index}` ? (
                                                                        <Check className="h-3 w-3 text-emerald-500" />
                                                                    ) : (
                                                                        <Copy className="h-3 w-3" />
                                                                    )}
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <span className="text-muted-foreground">Múi giờ:</span>
                                                        <div className="mt-0.5 font-medium text-foreground">
                                                            {resolveTimezoneLabel(acc.timezone_name)}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <span className="text-muted-foreground">Chi tiêu (Spend):</span>
                                                        <div className="mt-0.5 font-semibold text-foreground">
                                                            {Number(acc.amount_spent ?? 0).toFixed(2)}{' '}
                                                            {acc.currency || 'USD'}
                                                        </div>
                                                    </div>

                                                    {acc.payment_card && (
                                                        <div>
                                                            <span className="text-muted-foreground">Thẻ liên kết:</span>
                                                            <div className="mt-0.5 font-medium text-foreground">
                                                                {acc.payment_card}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* 4. Cấu hình yêu cầu từ khách khi đặt đơn (Order Requirements) */}
                        <div className="rounded-lg border bg-card p-4 shadow-sm">
                            <div className="mb-3 flex items-center gap-2 font-semibold text-foreground">
                                <Layers className="h-4 w-4 text-indigo-500" />
                                <span>Cấu hình yêu cầu khi đặt đơn (Order Config)</span>
                            </div>

                            {rawAccounts.length > 0 ? (
                                <div className="space-y-3">
                                    {rawAccounts.map((acc, idx) => (
                                        <div key={idx} className="rounded-md border bg-muted/30 p-3 text-xs">
                                            <div className="mb-2 font-semibold text-foreground">
                                                Tài khoản yêu cầu #{idx + 1}
                                            </div>
                                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                                                {acc.meta_email && (
                                                    <div>
                                                        <span className="text-muted-foreground">Email BM:</span>{' '}
                                                        <span className="font-medium">{acc.meta_email}</span>
                                                    </div>
                                                )}
                                                {acc.display_name && (
                                                    <div>
                                                        <span className="text-muted-foreground">Tên hiển thị:</span>{' '}
                                                        <span className="font-medium">{acc.display_name}</span>
                                                    </div>
                                                )}
                                                {acc.timezone_bm && (
                                                    <div>
                                                        <span className="text-muted-foreground">Múi giờ:</span>{' '}
                                                        <span className="font-medium">
                                                            {resolveTimezoneLabel(acc.timezone_bm)}
                                                        </span>
                                                    </div>
                                                )}
                                                {acc.bm_ids && acc.bm_ids.length > 0 && (
                                                    <div className="col-span-full">
                                                        <span className="text-muted-foreground">BM ID yêu cầu:</span>{' '}
                                                        <span className="font-mono">{acc.bm_ids.join(', ')}</span>
                                                    </div>
                                                )}
                                                {acc.fanpages && acc.fanpages.length > 0 && (
                                                    <div className="col-span-full">
                                                        <span className="text-muted-foreground">Fanpages:</span>{' '}
                                                        <span className="font-medium">{acc.fanpages.join(', ')}</span>
                                                    </div>
                                                )}
                                                {acc.websites && acc.websites.length > 0 && (
                                                    <div className="col-span-full">
                                                        <span className="text-muted-foreground">Websites:</span>{' '}
                                                        <span className="font-medium">{acc.websites.join(', ')}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 gap-2.5 text-xs sm:grid-cols-2">
                                    {config.meta_email && (
                                        <div>
                                            <span className="text-muted-foreground">Email BM:</span>{' '}
                                            <span className="font-medium">{config.meta_email}</span>
                                        </div>
                                    )}
                                    {config.display_name && (
                                        <div>
                                            <span className="text-muted-foreground">Tên hiển thị:</span>{' '}
                                            <span className="font-medium">{config.display_name}</span>
                                        </div>
                                    )}
                                    {config.bm_id && (
                                        <div>
                                            <span className="text-muted-foreground">BM ID:</span>{' '}
                                            <span className="font-mono">{config.bm_id}</span>
                                        </div>
                                    )}
                                    {config.timezone_bm && (
                                        <div>
                                            <span className="text-muted-foreground">Múi giờ:</span>{' '}
                                            <span className="font-medium">
                                                {resolveTimezoneLabel(config.timezone_bm)}
                                            </span>
                                        </div>
                                    )}
                                    {config.info_fanpage && (
                                        <div className="col-span-full">
                                            <span className="text-muted-foreground">Fanpage:</span>{' '}
                                            <span className="font-medium">{config.info_fanpage}</span>
                                        </div>
                                    )}
                                    {config.info_website && (
                                        <div className="col-span-full">
                                            <span className="text-muted-foreground">Website:</span>{' '}
                                            <span className="font-medium">{config.info_website}</span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </ScrollArea>

                <DialogFooter className="border-t px-6 py-3">
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Đóng
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
