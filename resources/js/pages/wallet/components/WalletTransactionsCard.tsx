import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Clock,
    ExternalLink,
    Gift,
    Loader2,
    Percent,
    RefreshCcw,
    Wallet as WalletIcon,
    Ban,
    CheckCircle2,
    XCircle,
    ShoppingCart,
} from 'lucide-react';
import type { WalletTransaction } from '@/pages/wallet/types/type';
import { cn } from '@/lib/utils';
import { TRANSACTION_TYPE, TRANSACTION_STATUS, TRANSACTION_TYPE_MAP, TRANSACTION_STATUS_MAP } from '@/pages/wallet/types/constants';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    transactions: WalletTransaction[];
};

const formatter = new Intl.NumberFormat('vi-VN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const WalletTransactionsCard = ({ t, transactions }: Props) => {
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
                return <Loader2 className="h-4 w-4 text-amber-500 animate-spin" />;
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
                                const dateA = a.createdAt ? new Date(a.createdAt).getTime() : 0;
                                const dateB = b.createdAt ? new Date(b.createdAt).getTime() : 0;
                                return dateB - dateA;
                            })
                            .map((tx) => {
                                const statusClass = getStatusColor(tx.status);
                                const explorerUrl = getExplorerUrl(tx.network, tx.txHash);

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
                                                        {t(`wallet.transaction_type.${getTypeKey(tx.type)}`, {
                                                            defaultValue: String(tx.type),
                                                        })}
                                                    </span>
                                                    <Badge className={cn(statusClass, 'text-xs')}>
                                                        {t(`wallet.transaction_status.${getStatusKey(tx.status)}`, {
                                                            defaultValue: String(tx.status),
                                                        })}
                                                    </Badge>
                                                </div>
                                                {tx.description && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {tx.description}
                                                    </p>
                                                )}
                                                <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                                    {getStatusIcon(tx.status)}
                                                    {tx.createdAt && (
                                                        <span>{getTimeSince(tx.createdAt)}</span>
                                                    )}
                                                    {tx.network && (
                                                        <span className="ml-2">• {tx.network}</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex flex-col items-start gap-2 md:items-end">
                                            <div
                                                className={cn(
                                                    'text-base font-semibold',
                                                    tx.amount >= 0 ? 'text-green-600' : 'text-red-600'
                                                )}
                                            >
                                                {formatUSDT(tx.amount)}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                {tx.network && <span className="uppercase">{tx.network}</span>}
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
                                                            aria-label={t('wallet.view_on_explorer')}
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


