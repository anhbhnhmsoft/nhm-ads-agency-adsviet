import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import { CustomerListItem } from '@/pages/user/types/type';
import { userRolesLabel, _WalletStatus } from '@/lib/types/constants';
import { Check, OctagonX } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useWallet } from '@/pages/user/hooks/use-wallet';
import { wallet_lock, wallet_unlock, wallet_reset_password } from '@/routes';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: CustomerListItem | null;
};

export default function UserInfoDialog({ open, onOpenChange, user }: Props) {
    const { t } = useTranslation();
    const [walletPassword, setWalletPassword] = useState('');
    const { wallet, loading: walletLoading, error: walletError, refetch: refetchWallet } = useWallet(
        user?.id,
        open
    );

    useEffect(() => {
        if (!open) {
            setWalletPassword('');
        }
    }, [open]);

    if (!user) return null;

    const walletStatus = wallet?.status;
    const walletBalanceDisplay = (() => {
        const raw = wallet?.balance ?? '0.00';
        const num = Number(raw);
        if (Number.isNaN(num)) return '0.00';
        return num.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    })();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-5xl w-full">
                <DialogHeader>
                    <DialogTitle>{t('user.customer_info')}</DialogTitle>
                </DialogHeader>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.name')}</label>
                        <div className="text-sm">{user.name}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.username')}</label>
                        <div className="text-sm">{user.username}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.phone')}</label>
                        <div className="text-sm">{user.phone || '-'}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.role')}</label>
                        <div className="text-sm">{t(userRolesLabel[user.role])}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.account_active')}</label>
                        <div className="flex items-center gap-2">
                            {!user.disabled ? (
                                <>
                                    <Check className="size-4 text-green-500" />
                                    <span className="text-sm">{t('common.active')}</span>
                                </>
                            ) : (
                                <>
                                    <OctagonX className="size-4 text-red-500" />
                                    <span className="text-sm">{t('common.disabled')}</span>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.referral_code')}</label>
                        <div className="text-sm">{user.referral_code}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.social_authentication')}</label>
                        <div className="flex flex-col gap-1">
                            {user.using_telegram && (
                                <div className="text-sm">{t('common.using_telegram')}</div>
                            )}
                            {user.using_whatsapp && (
                                <div className="text-sm">{t('common.using_whatsapp')}</div>
                            )}
                            {!user.using_telegram && !user.using_whatsapp && (
                                <div className="text-sm text-gray-400">-</div>
                            )}
                        </div>
                    </div>
                    <div className="col-span-full border-t pt-4 mt-2">
                        <div className="text-base font-medium mb-2">Ví nội bộ</div>
                        {walletLoading ? (
                            <div className="text-sm text-gray-500">{t('wallet.loading')}</div>
                        ) : walletError ? (
                            <div className="text-sm text-red-500">{t('wallet.error')}: {walletError}</div>
                        ) : (
                            <>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="grid gap-1">
                                        <label className="text-sm font-medium text-gray-500">{t('wallet.balance')}</label>
                                        <div className="text-sm">{walletBalanceDisplay} USDT</div>
                                    </div>
                                    <div className="grid gap-1">
                                        <label className="text-sm font-medium text-gray-500">{t('wallet.status')}</label>
                                        <div className="flex items-center gap-2">
                                            {walletStatus === _WalletStatus.LOCKED ? (
                                                <>
                                                    <OctagonX className="size-4 text-red-500" />
                                                    <span className="text-sm">{t('wallet.locked')}</span>
                                                </>
                                            ) : (
                                                <>
                                                    <Check className="size-4 text-green-500" />
                                                    <span className="text-sm">{t('wallet.active')}</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 mt-4">
                                    {walletStatus === _WalletStatus.LOCKED ? (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => {
                                                router.post(
                                                    wallet_unlock({ userId: user.id }).url,
                                                    {},
                                                    {
                                                        preserveScroll: true,
                                                        onSuccess: () => refetchWallet(),
                                                    }
                                                );
                                            }}
                                        >
                                            {t('wallet.unlock')}
                                        </Button>
                                    ) : (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => {
                                                router.post(
                                                    wallet_lock({ userId: user.id }).url,
                                                    {},
                                                    {
                                                        preserveScroll: true,
                                                        onSuccess: () => refetchWallet(),
                                                    }
                                                );
                                            }}
                                        >
                                            {t('wallet.lock')}
                                        </Button>
                                    )}
                                    <Input
                                        placeholder={t('wallet.new_password')}
                                        type="password"
                                        value={walletPassword}
                                        onChange={(e) => setWalletPassword(e.target.value)}
                                        className="flex-1"
                                    />
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => {
                                            if (walletPassword) {
                                                router.post(
                                                    wallet_reset_password({ userId: user.id }).url,
                                                    { password: walletPassword },
                                                    {
                                                        preserveScroll: true,
                                                        onSuccess: () => {
                                                            setWalletPassword('');
                                                            refetchWallet();
                                                        },
                                                    }
                                                );
                                            }
                                        }}
                                    >
                                        {t('wallet.reset_password')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
