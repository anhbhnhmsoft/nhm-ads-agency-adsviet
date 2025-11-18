import React, { ReactNode, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { useForm, router, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { wallet_my_top_up, wallet_my_withdraw, wallet_change_password } from '@/routes';
import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';
import type { WalletIndexProps } from '@/pages/wallet/types/type';
import WalletInfoCard from './components/WalletInfoCard';
import WalletActionsTabs from './components/WalletActionsTabs';
import WalletTransactionsCard from './components/WalletTransactionsCard';
import PendingDepositCard from './components/PendingDepositCard';

const WalletIndex = ({
    wallet,
    walletError,
    networks = [],
    pending_deposit = null,
}: WalletIndexProps) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const [activeTab, setActiveTab] = useState<'topup' | 'withdraw' | 'password'>('topup');

    const topUpForm = useForm({
        network: (networks[0]?.key as 'BEP20' | 'TRC20' | undefined) || undefined,
        amount: '',
    });

    const withdrawForm = useForm({
        amount: '',
        password: '',
        bank_name: '',
        account_holder: '',
        account_number: '',
    });

    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        confirm_password: '',
    });

    const handleTopUp = (e: React.FormEvent) => {
        e.preventDefault();
        topUpForm.post(wallet_my_top_up().url, {
            onSuccess: () => {
                topUpForm.reset('amount');
                router.reload({ only: ['pending_deposit'] });
            },
        });
    };

    const handleWithdraw = (e: React.FormEvent) => {
        e.preventDefault();
        withdrawForm.post(wallet_my_withdraw().url, {
            onSuccess: () => {
                withdrawForm.reset();
                router.reload({ only: ['wallet'] });
            },
        });
    };

    const handleChangePassword = (e: React.FormEvent) => {
        e.preventDefault();
        if (passwordForm.data.new_password !== passwordForm.data.confirm_password) {
            passwordForm.setError('confirm_password', t('wallet.password_not_match', { defaultValue: 'Mật khẩu xác nhận không khớp' }));
            return;
        }
        // Chỉ gửi current_password và new_password, không gửi confirm_password
        passwordForm.setData({
            current_password: passwordForm.data.current_password || undefined,
            new_password: passwordForm.data.new_password,
            confirm_password: '',
        });
        passwordForm.post(wallet_change_password().url, {
            onSuccess: () => {
                passwordForm.reset();
                router.reload({ only: ['wallet'] });
            },
        });
    };

    return (
        <div>
            <Head title={t('menu.my_wallet')} />
            <h1 className="text-xl font-semibold">
                {t('menu.my_wallet')}
            </h1>

            {walletError && (
                <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                    {walletError}
                </div>
            )}
            
            {!walletError && !wallet && (
                <div className="mt-4 rounded-lg border bg-yellow-50 p-4 text-yellow-800 text-sm">
                    {t('wallet.not_found', { defaultValue: 'Chưa có ví. Vui lòng liên hệ admin.' })}
                </div>
            )}

            {wallet && (
                <>
                    <div className="mt-4 space-y-4">
                        <WalletInfoCard t={t} wallet={wallet} />
                        {pending_deposit && (
                            <PendingDepositCard t={t} pending={pending_deposit} />
                        )}
                        <WalletActionsTabs
                            t={t}
                            isAdmin={isAdmin}
                            networks={networks}
                            activeTab={activeTab}
                            setActiveTab={setActiveTab}
                            topUpForm={topUpForm}
                            handleTopUp={handleTopUp}
                            withdrawForm={withdrawForm}
                            handleWithdraw={handleWithdraw}
                            passwordForm={passwordForm}
                            handleChangePassword={handleChangePassword}
                            walletHasPassword={wallet?.has_password}
                        />
                    </div>

                    <div className="mt-4">
                        <WalletTransactionsCard
                            t={t}
                            transactions={wallet.transactions ?? []}
                        />
                    </div>
                </>
            )}
        </div>
    );
}

WalletIndex.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'menu.my_wallet' }]} children={page} />
);

export default WalletIndex;

