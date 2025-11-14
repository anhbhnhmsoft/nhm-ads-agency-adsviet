import React from 'react';
import QRCode from 'react-qr-code';
import { Button } from '@/components/ui/button';
import type { PendingDeposit } from '@/pages/wallet/types/type';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    pending: PendingDeposit;
};

const PendingDepositCard = ({ t, pending }: Props) => {
    const qrValue = pending.pay_address || pending.deposit_address || '';

    return (
        <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-8 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-3">
                    <div className="mb-2 font-semibold">
                        {t('wallet.pending_deposit', { defaultValue: 'Thông tin lệnh nạp đang chờ' })}
                    </div>
                    <div>
                        <div className="text-sm">{t('wallet.amount')}</div>
                        <div className="font-semibold">{pending.amount} USDT</div>
                    </div>
                    {(pending.pay_address || pending.deposit_address) && (
                        <div>
                            <div className="text-sm">
                                {t('wallet.deposit_address', { defaultValue: 'Địa chỉ ví' })}
                            </div>
                            <div className="font-mono break-all text-sm">
                                {pending.pay_address ?? pending.deposit_address}
                            </div>
                        </div>
                    )}
                    <form
                        method="post"
                        action={`/wallets/deposit/${pending.id}/cancel`}
                        className="pt-2"
                    >
                        <input
                            type="hidden"
                            name="_token"
                            value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || ''}
                        />
                        <Button type="submit" variant="secondary">
                            {t('wallet.back_cancel', { defaultValue: 'Quay lại / Hủy lệnh' })}
                        </Button>
                    </form>
                </div>

                <div className="flex items-center justify-center">
                    {qrValue ? (
                        <div className="rounded-lg border bg-white p-3 shadow-sm">
                            <QRCode
                                value={qrValue}
                                size={156}
                                level="M"
                                aria-label="Payment QR"
                            />
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
};

export default PendingDepositCard;