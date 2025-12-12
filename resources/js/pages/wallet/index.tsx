import React, { ReactNode, useState, useEffect, useRef } from 'react';
import AppLayout from '@/layouts/app-layout';
import { toast } from 'sonner';
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
        crypto_address: '',
        network: undefined as 'TRC20' | 'BEP20' | undefined,
        withdraw_type: 'bank' as 'bank' | 'usdt',
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
        
        // Gửi dữ liệu theo withdraw_type
        const withdrawType = withdrawForm.data.withdraw_type || 'bank';
        
        const submitData: Record<string, any> = {
            amount: withdrawForm.data.amount,
            withdraw_type: withdrawType,
        };

        if (withdrawForm.data.password) {
            submitData.password = withdrawForm.data.password;
        }

        if (withdrawType === 'usdt') {
            submitData.crypto_address = withdrawForm.data.crypto_address;
            submitData.network = withdrawForm.data.network;
        } else {
            submitData.bank_name = withdrawForm.data.bank_name;
            submitData.account_holder = withdrawForm.data.account_holder;
            submitData.account_number = withdrawForm.data.account_number;
        }

        router.post(wallet_my_withdraw().url, submitData, {
            preserveScroll: true,
            onSuccess: () => {
                withdrawForm.reset();
                router.reload({ only: ['wallet'] });
                toast.success(t('wallet.withdraw_created', { defaultValue: 'Tạo lệnh rút tiền thành công' }));
            },
            onError: (errors) => {
                Object.keys(errors).forEach((key) => {
                    withdrawForm.setError(key as any, errors[key] as string);
                });
                const firstError = Object.values(errors)[0] as string | undefined;
                if (firstError) {
                    toast.error(firstError);
                } else {
                    toast.error(t('common.error', { defaultValue: 'Đã xảy ra lỗi. Vui lòng thử lại.' }));
                }
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

    // Polling để tự động reload khi có pending_deposit
    const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);
    useEffect(() => {
        // Chỉ polling khi có pending_deposit và không phải admin
        if (pending_deposit && !isAdmin) {
            pollingIntervalRef.current = setInterval(() => {
                router.reload({ 
                    only: ['pending_deposit', 'wallet'],
                });
            }, 5000); // Poll mỗi 5 giây
        } else {
            if (pollingIntervalRef.current) {
                clearInterval(pollingIntervalRef.current);
                pollingIntervalRef.current = null;
            }
        }

        return () => {
            if (pollingIntervalRef.current) {
                clearInterval(pollingIntervalRef.current);
            }
        };
    }, [pending_deposit, isAdmin]);

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

