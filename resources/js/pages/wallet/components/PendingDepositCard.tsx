import React from 'react';
import QRCode from 'react-qr-code';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useCancelDeposit } from '../hooks/use-cancel-deposit';
import type { PendingDeposit } from '@/pages/wallet/types/type';
import { getTransactionDescription } from '@/lib/types/wallet-transaction-description';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    pending: PendingDeposit;
};

const PendingDepositCard = ({ t, pending }: Props) => {
    const referenceUrl = pending.reference_id?.split('|')[0] ?? null;
    const invoiceUrl = referenceUrl?.startsWith('http') ? referenceUrl : null;
    const qrValue = pending.pay_address || pending.deposit_address || invoiceUrl || '';
    const { cancelDeposit, loading } = useCancelDeposit();
    const [showConfirmDialog, setShowConfirmDialog] = React.useState(false);
    const description = pending.description ? getTransactionDescription(pending.description, t) : null;

    const handleCancel = () => {
        setShowConfirmDialog(true);
    };

    const handleConfirmCancel = () => {
        cancelDeposit(pending.id, () => {
            setShowConfirmDialog(false);
        });
    };

    return (
        <>
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
                        {description && (
                            <div className="rounded-md border border-yellow-300 bg-yellow-100 px-3 py-2 text-sm font-medium text-yellow-900 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-100">
                                {description}
                            </div>
                        )}
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
                        {invoiceUrl && (
                            <div>
                                <div className="text-sm">
                                    {t('wallet.payment_invoice', { defaultValue: 'Hóa đơn thanh toán' })}
                                </div>
                                <Button type="button" variant="outline" asChild>
                                    <a href={invoiceUrl} target="_blank" rel="noreferrer">
                                        {t('wallet.open_payment_invoice', { defaultValue: 'Mở hóa đơn thanh toán' })}
                                    </a>
                                </Button>
                            </div>
                        )}
                        <div className="pt-2">
                            <Button 
                                type="button" 
                                variant="secondary" 
                                onClick={handleCancel}
                                disabled={loading}
                            >
                                {t('wallet.back_cancel', { defaultValue: 'Hủy lệnh' })}
                            </Button>
                        </div>
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

            <AlertDialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('wallet.confirm_cancel_deposit', { defaultValue: 'Xác nhận hủy lệnh nạp' })}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {t('wallet.confirm_cancel_deposit_message', { 
                                defaultValue: 'Bạn có chắc chắn muốn hủy lệnh nạp này không?' 
                            })}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={loading}>
                            {t('common.cancel', { defaultValue: 'Hủy' })}
                        </AlertDialogCancel>
                        <AlertDialogAction 
                            onClick={handleConfirmCancel}
                            disabled={loading}
                        >
                            {t('common.confirm', { defaultValue: 'Xác nhận' })}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
};

export default PendingDepositCard;
