import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Ban,
    CheckCircle2,
    Eye,
    ExternalLink,
    Gift,
    Loader2,
    Percent,
    ReceiptText,
    RefreshCcw,
    Wallet as WalletIcon,
    X,
    XCircle,
} from 'lucide-react';
import type { WalletTransaction } from '@/pages/wallet/types/type';
import {
    TRANSACTION_STATUS,
    TRANSACTION_STATUS_MAP,
    TRANSACTION_TYPE,
    TRANSACTION_TYPE_MAP,
} from '@/pages/wallet/types/constants';
import { getTransactionDescription } from '@/lib/types/wallet-transaction-description';

const amountFormatter = new Intl.NumberFormat('vi-VN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const formatUSDT = (amount: number) => `${amountFormatter.format(Math.abs(amount))} USDT`;

const getTransactionIcon = (type?: number | null) => {
    switch (type) {
        case TRANSACTION_TYPE.DEPOSIT:
            return <ArrowDownCircle className="h-5 w-5 text-green-500" />;
        case TRANSACTION_TYPE.WITHDRAW:
            return <ArrowUpCircle className="h-5 w-5 text-red-500" />;
        case TRANSACTION_TYPE.REFUND:
            return <RefreshCcw className="h-5 w-5 text-blue-500" />;
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
            return <Loader2 className="h-4 w-4 text-amber-500 animate-spin" />;
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
    onViewWithdrawInfo?: (info: WalletTransaction['withdraw_info'] | null) => void;
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
                    {emptyMessage || t('transactions.no_transactions', { defaultValue: 'Không có giao dịch nào' })}
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {transactions.map((tx) => {
                const statusClass = getStatusColor(tx.status);
                const explorerUrl = showExplorerLink ? getExplorerUrl(tx.network, tx.txHash) : null;
                const isPending = tx.status === TRANSACTION_STATUS.PENDING;
                const isWithdraw = tx.type === TRANSACTION_TYPE.WITHDRAW;
                const hasWithdrawInfo = isWithdraw && tx.withdraw_info;
                const approving = approveLoadingId === tx.id;
                const cancelling = cancelLoadingId === tx.id;

                return (
                    <Card key={tx.id} className="rounded-lg border transition-colors hover:bg-gray-50">
                        <CardContent className="sm:flex items-center justify-between p-4">
                            <div className="flex items-center gap-3 flex-1">
                                {getTransactionIcon(tx.type)}
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-medium">
                                            {t(`wallet.transaction_type.${TRANSACTION_TYPE_MAP[tx.type] ?? 'unknown'}`, {
                                                defaultValue: String(tx.type),
                                            })}
                                        </span>
                                        <Badge className={cn(statusClass, 'text-xs')}>
                                            {t(`wallet.transaction_status.${TRANSACTION_STATUS_MAP[tx.status] ?? 'unknown'}`, {
                                                defaultValue: String(tx.status),
                                            })}
                                        </Badge>
                                        {isPending && canApprove && (
                                            <>
                                                <Button
                                                    size="sm"
                                                    variant="default"
                                                    onClick={() => onApprove?.(tx.id)}
                                                    disabled={approving || cancelling}
                                                >
                                                    {approving ? t('common.processing') : t('transactions.approve')}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => onCancel?.(tx.id)}
                                                    disabled={approving || cancelling}
                                                >
                                                    <X className="mr-1 h-3 w-3" />
                                                    {cancelling ? t('common.processing') : t('transactions.cancel')}
                                                </Button>
                                            </>
                                        )}
                                        {hasWithdrawInfo && onViewWithdrawInfo && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => onViewWithdrawInfo(tx.withdraw_info || null)}
                                            >
                                                <Eye className="mr-1 h-3 w-3" />
                                                {t('transactions.view_info', { defaultValue: 'Xem thông tin' })}
                                            </Button>
                                        )}
                                    </div>
                                    {tx.description && (
                                        <p className="text-sm text-gray-600">
                                            {getTransactionDescription(tx.description, t)}
                                        </p>
                                    )}
                                    {tx.user && (
                                        <p className="text-xs text-gray-500">
                                            {tx.user.name} (ID: {tx.user.id})
                                        </p>
                                    )}
                                    <div className="hidden sm:flex mt-1 items-center gap-2 text-xs text-gray-400">
                                        {getStatusIcon(tx.status)}
                                        {tx.createdAt && <span>{getTimeLabel(tx.createdAt)}</span>}
                                        {tx.network && <span className="ml-2">• {tx.network}</span>}
                                    </div>
                                </div>
                            </div>
                            <div className="sm:hidden mt-1 flex items-center gap-2 text-xs text-gray-400">
                                {getStatusIcon(tx.status)}
                                {tx.createdAt && <span>{getTimeLabel(tx.createdAt)}</span>}
                                {tx.network && <span className="ml-2">• {tx.network}</span>}
                            </div>
                            <div className="text-right">
                                <div className={cn('font-bold', tx.amount > 0 ? 'text-green-600' : 'text-red-600')}>
                                    {tx.amount > 0 ? '+' : '-'}
                                    {formatUSDT(tx.amount)}
                                </div>
                                {explorerUrl && (
                                    <a
                                        href={explorerUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                        {t('transactions.view_on_explorer', { defaultValue: 'Xem trên explorer' })}
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

