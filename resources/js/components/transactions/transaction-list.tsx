import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
    ArrowDownCircle,
    ArrowUpCircle,
    Ban,
    CheckCircle2,
    ExternalLink,
    Eye,
    Gift,
    Layers,
    Loader2,
    Percent,
    RefreshCcw,
    Wallet as WalletIcon,
    X,
    XCircle,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

const amountFormatter = new Intl.NumberFormat('vi-VN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const formatUSDT = (amount: number) =>
    `${amountFormatter.format(Math.abs(amount))} USDT`;

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
            return <Loader2 className="h-4 w-4 animate-spin text-amber-500" />;
        case TRANSACTION_STATUS.REJECTED:
        case TRANSACTION_STATUS.CANCELLED:
            return <XCircle className="h-4 w-4 text-red-500" />;
        default:
            return <Ban className="h-4 w-4 text-muted-foreground" />;
    }
};

const getExplorerUrl = (network?: string | null, txHash?: string | null) => {
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

const getTimeLabel = (isoDate?: string | null) => {
    if (!isoDate) return '';
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '';
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${hours}:${minutes} - ${day}/${month}/${year}`;
};

type TransactionListProps = {
    transactions: WalletTransaction[];
    canApprove?: boolean;
    onApprove?: (id: string) => void;
    onCancel?: (id: string) => void;
    approveLoadingId?: string | null;
    cancelLoadingId?: string | null;
    onViewWithdrawInfo?: (
        info: WalletTransaction['withdraw_info'] | null,
    ) => void;
    showExplorerLink?: boolean;
    emptyMessage?: string;
};

export function TransactionList({
    transactions,
    canApprove = false,
    onApprove,
    onCancel,
    approveLoadingId,
    cancelLoadingId,
    onViewWithdrawInfo,
    showExplorerLink = true,
    emptyMessage,
}: TransactionListProps) {
    const { t } = useTranslation();

    if (!transactions.length) {
        return (
            <div className="py-8 text-center">
                <WalletIcon className="mx-auto mb-4 h-12 w-12 text-gray-300" />
                <p className="text-gray-500">
                    {emptyMessage ||
                        t('transactions.no_transactions', {
                            defaultValue: 'Không có giao dịch nào',
                        })}
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {transactions.map((tx) => {
                const statusClass = getStatusColor(tx.status);
                const explorerUrl = showExplorerLink
                    ? getExplorerUrl(tx.network, tx.txHash)
                    : null;
                const isPending = tx.status === TRANSACTION_STATUS.PENDING;
                const isWithdraw = tx.type === TRANSACTION_TYPE.WITHDRAW;
                const hasWithdrawInfo = isWithdraw && tx.withdraw_info;
                const approving = approveLoadingId === tx.id;
                const cancelling = cancelLoadingId === tx.id;

                return (
                    <Card
                        key={tx.id}
                        className="rounded-lg border transition-colors hover:bg-gray-50"
                    >
                        <CardContent className="items-center justify-between p-4 sm:flex">
                            <div className="flex flex-1 items-center gap-3 min-w-0">
                                {getTransactionIcon(tx.type)}
                                <div className="flex-1 min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {t(
                                                `wallet.transaction_type.${TRANSACTION_TYPE_MAP[tx.type] ?? 'unknown'}`,
                                                {
                                                    defaultValue: String(
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
                                            {t(
                                                `wallet.transaction_status.${TRANSACTION_STATUS_MAP[tx.status] ?? 'unknown'}`,
                                                {
                                                    defaultValue: String(
                                                        tx.status,
                                                    ),
                                                },
                                            )}
                                        </Badge>
                                        {isPending && canApprove && (
                                            <>
                                                <Button
                                                    size="sm"
                                                    variant="default"
                                                    onClick={() =>
                                                        onApprove?.(tx.id)
                                                    }
                                                    disabled={
                                                        approving || cancelling
                                                    }
                                                >
                                                    {approving
                                                        ? t('common.processing')
                                                        : t(
                                                              'transactions.approve',
                                                          )}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        onCancel?.(tx.id)
                                                    }
                                                    disabled={
                                                        approving || cancelling
                                                    }
                                                >
                                                    <X className="mr-1 h-3 w-3" />
                                                    {cancelling
                                                        ? t('common.processing')
                                                        : t(
                                                              'transactions.cancel',
                                                          )}
                                                </Button>
                                            </>
                                        )}
                                        {hasWithdrawInfo &&
                                            onViewWithdrawInfo && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        onViewWithdrawInfo(
                                                            tx.withdraw_info ||
                                                                null,
                                                        )
                                                    }
                                                >
                                                    <Eye className="mr-1 h-3 w-3" />
                                                    {t(
                                                        'transactions.view_info',
                                                        {
                                                            defaultValue:
                                                                'Xem thông tin',
                                                        },
                                                    )}
                                                </Button>
                                            )}
                                    </div>
                                    {tx.description && (
                                        <p className="text-sm text-gray-600">
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
                                            (item) => item && (Number(item.spent ?? 0) > 0 || Number(item.fee ?? 0) > 0)
                                        );
                                        if (validDetails.length === 0) return null;

                                        const totalSpent = validDetails.reduce((sum, item) => sum + Number(item.spent ?? 0), 0);
                                        const totalFee = validDetails.reduce((sum, item) => sum + Number(item.fee ?? 0), 0);
                                        const summaryCurrency = validDetails.find(i => i.currency)?.currency || 'USD';

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
                                                <div className="mt-2 flex items-center justify-between border-t border-amber-200/60 pt-2 font-semibold text-amber-900 dark:border-amber-900/40 dark:text-amber-200">
                                                    <span>Tổng cộng:</span>
                                                    <div className="flex items-center gap-2 text-xs sm:text-sm">
                                                        <span>
                                                            Spend: <strong className="text-blue-600 dark:text-blue-400">{totalSpent.toFixed(2)} {summaryCurrency}</strong>
                                                        </span>
                                                        <span className="text-muted-foreground">|</span>
                                                        <span>
                                                            Phí: <strong className="text-red-600 dark:text-red-400">{totalFee.toFixed(2)} USDT</strong>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })()}
                                    {tx.user && (
                                        <p className="text-xs text-gray-500">
                                            {tx.user.name} (ID: {tx.user.id})
                                        </p>
                                    )}
                                    <div className="mt-1 hidden items-center gap-2 text-xs text-gray-400 sm:flex">
                                        {getStatusIcon(tx.status)}
                                        {tx.createdAt && (
                                            <span>
                                                {getTimeLabel(tx.createdAt)}
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
                            <div className="mt-1 flex items-center gap-2 text-xs text-gray-400 sm:hidden">
                                {getStatusIcon(tx.status)}
                                {tx.createdAt && (
                                    <span>{getTimeLabel(tx.createdAt)}</span>
                                )}
                                {tx.network && (
                                    <span className="ml-2">• {tx.network}</span>
                                )}
                            </div>
                            <div className="text-right">
                                <div
                                    className={cn(
                                        'font-bold',
                                        tx.amount > 0
                                            ? 'text-green-600'
                                            : 'text-red-600',
                                    )}
                                >
                                    {tx.amount > 0 ? '+' : '-'}
                                    {formatUSDT(tx.amount)}
                                </div>
                                {tx.balance_after !== null &&
                                    tx.balance_after !== undefined && (
                                        <div className="mt-1 text-xs text-gray-500">
                                            {t('transactions.balance_after', {
                                                defaultValue:
                                                    'Số dư sau giao dịch',
                                            })}
                                            : {formatUSDT(tx.balance_after)}
                                        </div>
                                    )}
                                {explorerUrl && (
                                    <a
                                        href={explorerUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="mt-1 inline-flex items-center gap-1 text-xs text-[#4285f4] hover:underline"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                        {t('transactions.view_on_explorer', {
                                            defaultValue: 'Xem trên explorer',
                                        })}
                                    </a>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
