import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import type { WalletData } from '@/pages/wallet/types/type';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    wallet: WalletData;
};

const WalletInfoCard = ({ t, wallet }: Props) => {
    const balanceDisplay = Number(wallet.balance).toLocaleString('vi-VN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const statusLabel = wallet?.status === 1 ? t('wallet.active') : t('wallet.locked');

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('wallet.info')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div>
                    <Label className="text-muted-foreground">
                        {t('wallet.balance')}
                    </Label>
                    <div className="mt-1 text-2xl font-semibold">{balanceDisplay} {t('wallet.currency')}</div>
                </div>
                <div>
                    <Label className="text-muted-foreground">
                        {t('wallet.status')}
                    </Label>
                    <div className="mt-1">
                        <span
                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                wallet.status === 1
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200'
                            }`}
                        >
                            {statusLabel}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
};

export default WalletInfoCard;


