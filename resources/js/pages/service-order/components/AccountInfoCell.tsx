import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { _PlatformType } from '@/lib/types/constants';
import { Copy, Eye, Layers } from 'lucide-react';
import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import type { AccountConfig, ServiceOrderConfigAccount } from '../types/type';

type AccountInfoCellProps = {
    config: ServiceOrderConfigAccount | null;
    platform?: number | null;
    packageBillingSource?: string | null;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
};

export const AccountInfoCell = ({
    config,
    platform,
    packageBillingSource,
    metaTimezones = [],
    googleTimezones = [],
}: AccountInfoCellProps) => {
    const { t } = useTranslation();

    if (!config) {
        return <span className="text-xs text-muted-foreground">-</span>;
    }

    const isMeta = platform === _PlatformType.META;

    const resolveTimezoneLabel = (value: string): string => {
        if (!value) return '';
        const options = isMeta ? metaTimezones : googleTimezones;
        const found = options.find((opt) => opt.value === value);
        return found ? found.label : value;
    };

    const accounts = config.accounts;
    if (Array.isArray(accounts) && accounts.length > 0) {
        const billingSource =
            packageBillingSource ||
            config.billing_source ||
            (config as any).payment_source;

        const handleCopyAllIds = (e: React.MouseEvent) => {
            e.stopPropagation();
            const allIds = accounts
                .map((a) => {
                    const bm = Array.isArray(a.bm_ids) && a.bm_ids.length > 0 ? a.bm_ids[0] : '';
                    return (a as any).account_id || bm || a.display_name || '';
                })
                .filter(Boolean)
                .join('\n');
            if (allIds) {
                navigator.clipboard.writeText(allIds);
                toast.success(t('common.copied_to_clipboard', { defaultValue: 'Đã copy danh sách ID vào clipboard!' }));
            }
        };

        const renderSingleAccountDetails = (account: AccountConfig, idx: number) => {
            const email = account.meta_email || '';
            const name = account.display_name || '';
            const bmIds = Array.isArray(account.bm_ids)
                ? account.bm_ids.filter((id: string) => id?.trim())
                : [];
            const fanpages = Array.isArray(account.fanpages)
                ? account.fanpages.filter((fp: string) => fp?.trim())
                : [];
            const websites = Array.isArray(account.websites)
                ? account.websites.filter((ws: string) => ws?.trim())
                : [];
            const timezone = account.timezone_bm || '';
            const assetAccess = account.asset_access || '';

            return (
                <div key={idx} className="rounded border bg-muted/30 p-2 text-xs">
                    <div className="mb-1 font-semibold text-primary">
                        {t('service_purchase.account_number', {
                            number: idx + 1,
                            defaultValue: `Tài khoản ${idx + 1}`,
                        })}
                    </div>
                    <div className="space-y-1">
                        {email && (
                            <div>
                                <span className="font-medium text-muted-foreground">Email:</span> {email}
                            </div>
                        )}
                        {name && (
                            <div>
                                <span className="font-medium text-muted-foreground">Tên:</span> {name}
                            </div>
                        )}
                        {bmIds.length > 0 && (
                            <div className="flex items-center gap-1">
                                <span className="font-medium text-muted-foreground">{isMeta ? 'BM ID:' : 'MCC ID:'}</span>
                                <span className="font-mono">{bmIds.join(', ')}</span>
                            </div>
                        )}
                        {timezone && (
                            <div>
                                <span className="font-medium text-muted-foreground">Múi giờ:</span> {resolveTimezoneLabel(timezone)}
                            </div>
                        )}
                        {fanpages.length > 0 && (
                            <div>
                                <span className="font-medium text-muted-foreground">Fanpage:</span> {fanpages.join(', ')}
                            </div>
                        )}
                        {websites.length > 0 && (
                            <div>
                                <span className="font-medium text-muted-foreground">Website:</span> {websites.join(', ')}
                            </div>
                        )}
                        {assetAccess && (
                            <div>
                                <span className="font-medium text-muted-foreground">Quyền:</span>{' '}
                                {assetAccess === 'full_asset' ? 'Full access' : 'Basic access'}
                            </div>
                        )}
                    </div>
                </div>
            );
        };

        // Nếu chỉ có 1 tài khoản, hiển thị bình thường
        if (accounts.length === 1) {
            return (
                <div className="space-y-2 text-xs">
                    {billingSource && (
                        <div className="rounded border border-indigo-100 bg-indigo-50/50 p-2 font-medium text-indigo-950">
                            <span className="font-semibold text-indigo-900">
                                {t('service_orders.form.billing_source_label', {
                                    defaultValue: 'Nguồn thanh toán',
                                })}:
                            </span>{' '}
                            {t(`service_orders.form.billing_sources.${billingSource}`, {
                                defaultValue: billingSource,
                            })}
                        </div>
                    )}
                    {renderSingleAccountDetails(accounts[0], 0)}
                </div>
            );
        }

        // Nếu có nhiều tài khoản (20 tài khoản...), hiển thị dạng THU GỌN với Popover danh sách giống Google Sheets (ảnh 1)
        const firstAccount = accounts[0];
        const firstBm = Array.isArray(firstAccount.bm_ids) && firstAccount.bm_ids.length > 0 ? firstAccount.bm_ids[0] : '';

        return (
            <div className="space-y-1.5 text-xs max-w-xs">
                {billingSource && (
                    <div className="rounded border border-indigo-100 bg-indigo-50/50 px-2 py-1 text-[11px] font-medium text-indigo-950">
                        <span className="font-semibold text-indigo-900">
                            {t('service_orders.form.billing_source_label', {
                                defaultValue: 'Nguồn',
                            })}:
                        </span>{' '}
                        {t(`service_orders.form.billing_sources.${billingSource}`, {
                            defaultValue: billingSource === 'customer_card'
                                ? 'Thẻ khách'
                                : billingSource === 'adviet_card'
                                    ? 'Thẻ Adviet'
                                    : 'Hạn mức NCC',
                        })}
                    </div>
                )}

                <div className="rounded-lg border bg-card p-2 shadow-sm space-y-1.5">
                    <div className="flex items-center justify-between gap-1.5">
                        <Badge variant="outline" className="bg-primary/10 text-primary border-primary/20 text-[11px] font-medium px-2 py-0.5">
                            <Layers className="h-3 w-3 mr-1" />
                            {accounts.length} {t('service_orders.table.accounts_count', { defaultValue: 'tài khoản' })}
                        </Badge>

                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    className="h-6 px-2 text-[11px] gap-1 hover:bg-primary hover:text-primary-foreground transition-colors"
                                >
                                    <Eye className="h-3 w-3" />
                                    <span>{t('common.view_list', { defaultValue: 'Xem danh sách' })}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                align="start"
                                side="bottom"
                                className="w-80 p-0 shadow-lg border-border"
                            >
                                <div className="flex items-center justify-between border-b px-3 py-2 bg-muted/40">
                                    <div className="font-medium text-xs flex items-center gap-1.5">
                                        <Layers className="h-3.5 w-3.5 text-primary" />
                                        <span>Danh sách {accounts.length} tài khoản</span>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleCopyAllIds}
                                        className="h-6 px-1.5 text-[11px] gap-1 text-muted-foreground hover:text-foreground"
                                        title="Copy tất cả ID"
                                    >
                                        <Copy className="h-3 w-3" />
                                        <span>Copy hết</span>
                                    </Button>
                                </div>
                                <ScrollArea className="max-h-72 p-2">
                                    <div className="space-y-1.5">
                                        {accounts.map((acc, idx) => {
                                            const bm = Array.isArray(acc.bm_ids) && acc.bm_ids.length > 0 ? acc.bm_ids.join(', ') : '';
                                            const idDisplay = (acc as any).account_id || bm || acc.meta_email || '-';
                                            return (
                                                <div
                                                    key={idx}
                                                    className="flex items-center justify-between gap-2 rounded border bg-background p-1.5 text-xs hover:bg-muted/50 transition-colors"
                                                >
                                                    <div className="flex items-center gap-1.5 min-w-0">
                                                        <span className="font-mono text-[10px] text-muted-foreground w-4 shrink-0">
                                                            #{idx + 1}
                                                        </span>
                                                        <div className="min-w-0">
                                                            <div className="font-medium truncate text-[11px]">
                                                                {acc.display_name || `Account ${idx + 1}`}
                                                            </div>
                                                            <div className="font-mono text-[10px] text-muted-foreground truncate">
                                                                {idDisplay}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {bm && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-5 w-5 shrink-0 text-muted-foreground hover:text-foreground"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                navigator.clipboard.writeText(bm);
                                                                toast.success(`Đã copy: ${bm}`);
                                                            }}
                                                            title="Copy ID"
                                                        >
                                                            <Copy className="h-3 w-3" />
                                                        </Button>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </ScrollArea>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <div className="text-[11px] text-muted-foreground truncate font-mono">
                        #{1}: {firstAccount.display_name || firstBm || firstAccount.meta_email || 'TK 1'}{' '}
                        <span className="text-[10px] text-primary">
                            (+{accounts.length - 1} khác)
                        </span>
                    </div>
                </div>
            </div>
        );
    }

    const email = (config.meta_email as string) || '';
    const name = (config.display_name as string) || '';
    const bm = (config.bm_id as string) || '';
    const fanpage = (config.info_fanpage as string) || '';
    const website = (config.info_website as string) || '';
    const timezone = (config.timezone_bm as string) || '';
    const billingSource =
        packageBillingSource ||
        config.billing_source ||
        (config as any).payment_source ||
        '';

    if (!email && !name && !bm && !fanpage && !website && !timezone && !billingSource) {
        return <span className="text-xs text-muted-foreground">-</span>;
    }

    return (
        <div className="space-y-1 text-xs">
            {billingSource && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.form.billing_source_label', {
                            defaultValue: 'Nguồn thanh toán',
                        })}:
                    </span>{' '}
                    <span className="font-semibold text-indigo-700">
                        {t(`service_orders.form.billing_sources.${billingSource}`, {
                            defaultValue: billingSource === 'customer_card'
                                ? 'Thẻ của khách'
                                : billingSource === 'adviet_card'
                                    ? 'Thẻ Adviet'
                                    : billingSource === 'supplier_credit_line'
                                        ? 'Hạn mức nhà cung cấp'
                                        : billingSource,
                        })}
                    </span>
                </div>
            )}
            {email && (
                <div>
                    <span className="font-medium">Email:</span> {email}
                </div>
            )}
            {name && (
                <div>
                    <span className="font-medium">Name:</span> {name}
                </div>
            )}
            {bm && (
                <div>
                    <span className="font-medium">
                        {isMeta ? 'BM ID' : 'MCC ID'}:
                    </span>{' '}
                    {bm}
                </div>
            )}
            {timezone && (
                <div>
                    <span className="font-medium">
                        {isMeta
                            ? t('service_purchase.timezone_bm_label', {
                                defaultValue: 'Múi giờ BM',
                            })
                            : t('service_purchase.timezone_mcc_label', {
                                defaultValue: 'Múi giờ MCC',
                            })}
                        :
                    </span>{' '}
                    {resolveTimezoneLabel(timezone)}
                </div>
            )}
            {fanpage && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.table.info_fanpage')}:
                    </span>{' '}
                    {fanpage}
                </div>
            )}
            {website && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.table.info_website')}:
                    </span>{' '}
                    {website}
                </div>
            )}
        </div>
    );
};
