import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getTransactionDescription } from '@/lib/types/wallet-transaction-description';
import { cn } from '@/lib/utils';
import {
    TRANSACTION_STATUS,
    TRANSACTION_STATUS_MAP,
    TRANSACTION_TYPE,
    TRANSACTION_TYPE_MAP,
} from '@/pages/wallet/types/constants';
import type { WalletTransaction } from '@/pages/wallet/types/type';
import {
    AlertTriangle,
    ArrowDownCircle,
    ArrowUpCircle,
    Ban,
    CheckCircle2,
    Clock,
    ExternalLink,
    Gift,
    Layers,
    Loader2,
    Percent,
    RefreshCcw,
    ShoppingCart,
    Wallet as WalletIcon,
    XCircle,
} from 'lucide-react';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    transactions: WalletTransaction[];
};

const formatter = new Intl.NumberFormat('vi-VN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const WalletTransactionsCard = ({ t, transactions }: Props) => {
    const underpaidDescription =
        'wallet.transaction_description.deposit_underpaid';

    const getTransactionIcon = (type?: number | null) => {
        switch (type) {
            case TRANSACTION_TYPE.DEPOSIT:
                return <ArrowDownCircle className="h-5 w-5 text-green-500" />;
            case TRANSACTION_TYPE.WITHDRAW:
                return <ArrowUpCircle className="h-5 w-5 text-red-500" />;
            case TRANSACTION_TYPE.REFUND:
            case TRANSACTION_TYPE.ACCOUNT_REFUND:
                return <RefreshCcw className="h-5 w-5 text-[#4285f4]" />;
            case TRANSACTION_TYPE.FEE:
                return <Percent className="h-5 w-5 text-amber-500" />;
            case TRANSACTION_TYPE.CASHBACK:
                return <Gift className="h-5 w-5 text-emerald-500" />;
            case TRANSACTION_TYPE.SERVICE_PURCHASE:
                return <ShoppingCart className="h-5 w-5 text-pink-500" />;
            default:
                return <WalletIcon className="h-5 w-5 text-muted-foreground" />;
        }
    };

    const getStatusColor = (status?: number | null) => {
        switch (status) {
            case TRANSACTION_STATUS.APPROVED:
            case TRANSACTION_STATUS.COMPLETED:
                return 'bg-green-500 text-white';
            case TRANSACTION_STATUS.PENDING:
                return 'bg-amber-500 text-white';
            case TRANSACTION_STATUS.REJECTED:
            case TRANSACTION_STATUS.CANCELLED:
                return 'bg-red-500 text-white';
            default:
                return 'bg-muted text-foreground';
        }
    };

    const getStatusIcon = (status?: number | null) => {
        switch (status) {
            case TRANSACTION_STATUS.APPROVED:
            case TRANSACTION_STATUS.COMPLETED:
                return <CheckCircle2 className="h-4 w-4 text-green-500" />;
            case TRANSACTION_STATUS.PENDING:
                return (
                    <Loader2 className="h-4 w-4 animate-spin text-amber-500" />
                );
            case TRANSACTION_STATUS.REJECTED:
            case TRANSACTION_STATUS.CANCELLED:
                return <XCircle className="h-4 w-4 text-red-500" />;
            default:
                return <Ban className="h-4 w-4 text-muted-foreground" />;
        }
    };

    const getTimeSince = (isoDate?: string | null) => {
        if (!isoDate) return '';
        const date = new Date(isoDate);
        if (Number.isNaN(date.getTime())) return '';

        // Format: "HH:mm - dd/MM/yyyy"
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${hours}:${minutes} - ${day}/${month}/${year}`;
    };

    const formatUSDT = (amount: number) => {
        const formatted = formatter.format(Math.abs(amount));
        return `${amount >= 0 ? '+' : '-'}${formatted} USDT`;
    };

    // Map type number sang translation key
    const getTypeKey = (type: number): string => {
        return TRANSACTION_TYPE_MAP[type] || 'unknown';
    };

    // Map status number sang translation key
    const getStatusKey = (status: number): string => {
        return TRANSACTION_STATUS_MAP[status] || 'unknown';
    };

    const getExplorerUrl = (
        network?: string | null,
        txHash?: string | null,
    ) => {
        if (!network || !txHash) return null;
        switch (network.toUpperCase()) {
            case 'BEP20':
                return `https://bscscan.com/tx/${txHash}`;
            case 'TRC20':
                return `https://tronscan.org/#/transaction/${txHash}`;
            default:
                return null;
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Clock className="h-5 w-5" />
                    {t('service_user.transaction_history')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {transactions.length === 0 ? (
                    <div className="py-8 text-center">
                        <WalletIcon className="mx-auto mb-4 h-12 w-12 text-gray-300" />
                        <p className="text-gray-500">
                            {t('service_user.no_transactions')}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {[...transactions]
                            .sort((a, b) => {
                                const dateA = a.createdAt
                                    ? new Date(a.createdAt).getTime()
                                    : 0;
                                const dateB = b.createdAt
                                    ? new Date(b.createdAt).getTime()
                                    : 0;
                                return dateB - dateA;
                            })
                            .map((tx) => {
                                const isUnderpaid =
                                    tx.description === underpaidDescription;
                                const statusClass = isUnderpaid
                                    ? 'bg-yellow-500 text-white'
                                    : getStatusColor(tx.status);
                                const statusLabel = isUnderpaid
                                    ? t('wallet.transaction_status.underpaid', {
                                          defaultValue: 'Thanh toán thiếu',
                                      })
                                    : t(
                                          `wallet.transaction_status.${getStatusKey(tx.status)}`,
                                          {
                                              defaultValue: String(tx.status),
                                          },
                                      );
                                const statusIcon = isUnderpaid ? (
                                    <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                ) : (
                                    getStatusIcon(tx.status)
                                );
                                const explorerUrl = getExplorerUrl(
                                    tx.network,
                                    tx.txHash,
                                );

                                return (
                                    <div
                                        key={tx.id}
                                        className="flex flex-col gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/30 md:flex-row md:items-center md:justify-between"
                                    >
                                        <div className="flex flex-1 items-start gap-3">
                                            {getTransactionIcon(tx.type)}
                                            <div className="space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium">
                                                        {t(
                                                            `wallet.transaction_type.${getTypeKey(tx.type)}`,
                                                            {
                                                                defaultValue:
                                                                    String(
                                                                        tx.type,
                                                                    ),
                                                            },
                                                        )}
                                                    </span>
                                                    <Badge
                                                        className={cn(
                                                            statusClass,
                                                            'text-xs',
                                                        )}
                                                    >
                                                        {statusLabel}
                                                    </Badge>
                                                </div>
                                                {tx.description && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {getTransactionDescription(
                                                            tx.description,
                                                            t,
                                                        )}
                                                    </p>
                                                )}
                                                {(() => {
                                                    const rawDetails = tx.withdraw_info?.accounts_detail;
                                                    if (!rawDetails) return null;
                                                    const detailsList: any[] = Array.isArray(rawDetails)
                                                        ? rawDetails
                                                        : typeof rawDetails === 'object'
                                                          ? Object.values(rawDetails)
                                                          : [];
                                                    const validDetails = detailsList.filter(
                                                        (item) => item && (Number(item.spent ?? 0) > 0 || Number(item.fee ?? 0) > 0 || item.account_id)
                                                    );
                                                    if (validDetails.length === 0) return null;

                                                    return (
                                                        <div className="mt-2.5 rounded-lg border border-amber-200/90 bg-amber-50/70 p-3 text-xs dark:border-amber-900/50 dark:bg-amber-950/20">
                                                            <div className="mb-2 flex items-center justify-between font-semibold text-amber-900 dark:text-amber-200">
                                                                <span className="flex items-center gap-1.5">
                                                                    <Layers className="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                                                                    <span>Chi tiết tài khoản (Accounts Breakdown):</span>
                                                                </span>
                                                                <span className="text-[11px] font-normal text-amber-700/80 dark:text-amber-300/80">
                                                                    {validDetails.length} tài khoản
                                                                </span>
                                                            </div>
                                                            <div className="divide-y divide-amber-200/60 dark:divide-amber-900/40">
                                                                {validDetails.map((item, idx) => {
                                                                    const cleanId = (item.account_id || '').replace(/^act_/, '');
                                                                    const displayActId = cleanId ? `act_${cleanId}` : '-';
                                                                    const displayName = item.name || displayActId;
                                                                    const spent = Number(item.spent ?? 0);
                                                                    const fee = Number(item.fee ?? 0);
                                                                    const currency = item.currency || 'USD';

                                                                    return (
                                                                        <div
                                                                            key={idx}
                                                                            className="flex flex-col gap-1 py-1.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                                                        >
                                                                            <div className="flex items-center gap-1.5 min-w-0">
                                                                                <span className="font-bold text-amber-500">•</span>
                                                                                <span className="font-mono font-medium text-slate-800 dark:text-slate-200">
                                                                                    {displayActId}
                                                                                </span>
                                                                                {item.name && item.name !== displayActId && (
                                                                                    <span className="truncate text-slate-600 dark:text-slate-400">
                                                                                        ({item.name})
                                                                                    </span>
                                                                                )}
                                                                            </div>
                                                                            <div className="flex items-center gap-2 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                                                                                <span>
                                                                                    Spend:{' '}
                                                                                    <strong className="font-semibold text-blue-600 dark:text-blue-400">
                                                                                        {spent.toFixed(2)} {currency}
                                                                                    </strong>
                                                                                </span>
                                                                                <span className="text-muted-foreground">|</span>
                                                                                <span>
                                                                                    Phí:{' '}
                                                                                    <strong className="font-semibold text-red-600 dark:text-red-400">
                                                                                        {fee.toFixed(2)} USDT
                                                                                    </strong>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    );
                                                })()}
                                                <div className="mt-1 hidden items-center gap-2 text-xs text-muted-foreground sm:flex">
                                                    {statusIcon}
                                                    {tx.createdAt && (
                                                        <span>
                                                            {getTimeSince(
                                                                tx.createdAt,
                                                            )}
                                                        </span>
                                                    )}
                                                    {tx.network && (
                                                        <span className="ml-2">
                                                            • {tx.network}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex flex-row items-start gap-2 sm:flex-col md:items-end">
                                            <div
                                                className={cn(
                                                    'text-base font-semibold',
                                                    tx.amount >= 0
                                                        ? 'text-green-600'
                                                        : 'text-red-600',
                                                )}
                                            >
                                                {formatUSDT(tx.amount)}
                                            </div>
                                            <div className="ms-4 mt-1 flex items-center gap-2 text-xs text-muted-foreground sm:hidden">
                                                {statusIcon}
                                                {tx.createdAt && (
                                                    <span>
                                                        {getTimeSince(
                                                            tx.createdAt,
                                                        )}
                                                    </span>
                                                )}
                                                {tx.network && (
                                                    <span className="ml-2">
                                                        • {tx.network}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="hidden items-center gap-2 text-xs text-muted-foreground sm:flex">
                                                {tx.network && (
                                                    <span className="uppercase">
                                                        {tx.network}
                                                    </span>
                                                )}
                                                {explorerUrl && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="h-7 w-7 p-0"
                                                        asChild
                                                    >
                                                        <a
                                                            href={explorerUrl}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            aria-label={t(
                                                                'wallet.view_on_explorer',
                                                            )}
                                                        >
                                                            <ExternalLink className="h-4 w-4" />
                                                        </a>
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

export default WalletTransactionsCard;
