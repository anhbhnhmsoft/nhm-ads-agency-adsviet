import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import type { InertiaFormProps } from '@inertiajs/react';
import type { Network, TopUpFormData, WalletTab, WithdrawFormData, PasswordFormData } from '@/pages/wallet/types/type';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    isAdmin: boolean;
    networks: Network[];
    activeTab: WalletTab;
    setActiveTab: (tab: WalletTab) => void;
    topUpForm: InertiaFormProps<TopUpFormData>;
    handleTopUp: (e: React.FormEvent) => void;
    withdrawForm: InertiaFormProps<WithdrawFormData>;
    handleWithdraw: (e: React.FormEvent) => void;
    passwordForm: InertiaFormProps<PasswordFormData>;
    handleChangePassword: (e: React.FormEvent) => void;
    walletHasPassword?: boolean;
};

const WalletActionsTabs = ({
    t,
    isAdmin,
    networks,
    activeTab,
    setActiveTab,
    topUpForm,
    handleTopUp,
    withdrawForm,
    handleWithdraw,
    passwordForm,
    handleChangePassword,
    walletHasPassword = false,
}: Props) => {
    if (isAdmin) return null;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('service_user.actions')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex space-x-2 border-b">
                    <button
                        onClick={() => setActiveTab('topup')}
                        className={`px-4 py-2 text-sm font-medium transition-colors ${
                            activeTab === 'topup'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {t('wallet.top_up')}
                    </button>
                    <button
                        onClick={() => setActiveTab('withdraw')}
                        className={`px-4 py-2 text-sm font-medium transition-colors ${
                            activeTab === 'withdraw'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {t('wallet.withdraw')}
                    </button>
                    <button
                        onClick={() => setActiveTab('password')}
                        className={`px-4 py-2 text-sm font-medium transition-colors ${
                            activeTab === 'password'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {t('service_user.change_password')}
                    </button>
                </div>

                <div className="mt-4">
                    {activeTab === 'topup' && (
                        <form onSubmit={handleTopUp} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="network">
                                    {t('service_user.choose_network')}
                                </Label>
                                <select
                                    id="network"
                                    className="border-input bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={topUpForm.data.network || ''}
                                    onChange={(e) => topUpForm.setData('network', e.target.value as 'BEP20' | 'TRC20')}
                                    disabled={networks.length === 0}
                                    required
                                >
                                    <option value="" disabled>{t('service_user.select_network_placeholder')}</option>
                                    {networks.map((n) => (
                                        <option key={n.key} value={n.key}>
                                            {n.key}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={topUpForm.errors.network as any} />
                                {networks.length === 0 && (
                                    <div className="text-sm text-muted-foreground">
                                        {t('service_user.network_not_configured')}
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="topup-amount">
                                    {t('wallet.amount')}
                                </Label>
                                <Input
                                    id="topup-amount"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    value={topUpForm.data.amount}
                                    onChange={(e) => topUpForm.setData('amount', e.target.value)}
                                    placeholder="1.00"
                                    required
                                />
                                <InputError message={topUpForm.errors.amount} />
                            </div>
                            <Button type="submit" disabled={topUpForm.processing || networks.length === 0}>
                                {topUpForm.processing
                                    ? t('common.processing')
                                    : t('service_user.create_deposit_order')}
                            </Button>
                        </form>
                    )}

                    {activeTab === 'withdraw' && (
                        <form onSubmit={handleWithdraw} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="withdraw-amount">
                                    {t('wallet.amount')}
                                </Label>
                                <Input
                                    id="withdraw-amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={withdrawForm.data.amount}
                                    onChange={(e) => withdrawForm.setData('amount', e.target.value)}
                                    placeholder="0.00"
                                    required
                                />
                                <InputError message={withdrawForm.errors.amount} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="withdraw-bank-name">
                                    {t('service_user.bank_name')}
                                </Label>
                                <Input
                                    id="withdraw-bank-name"
                                    type="text"
                                    value={withdrawForm.data.bank_name}
                                    onChange={(e) => withdrawForm.setData('bank_name', e.target.value)}
                                    placeholder={t('service_user.enter_bank_name')}
                                    required
                                />
                                <InputError message={withdrawForm.errors.bank_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="withdraw-account-holder">
                                    {t('service_user.account_holder')}
                                </Label>
                                <Input
                                    id="withdraw-account-holder"
                                    type="text"
                                    value={withdrawForm.data.account_holder}
                                    onChange={(e) => withdrawForm.setData('account_holder', e.target.value)}
                                    placeholder={t('service_user.enter_account_holder')}
                                    required
                                />
                                <InputError message={withdrawForm.errors.account_holder} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="withdraw-account-number">
                                    {t('service_user.account_number')}
                                </Label>
                                <Input
                                    id="withdraw-account-number"
                                    type="text"
                                    value={withdrawForm.data.account_number}
                                    onChange={(e) => withdrawForm.setData('account_number', e.target.value)}
                                    placeholder={t('service_user.enter_account_number')}
                                    required
                                />
                                <InputError message={withdrawForm.errors.account_number} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="withdraw-password">
                                    {t('service_user.wallet_password')}
                                </Label>
                                <Input
                                    id="withdraw-password"
                                    type="password"
                                    value={withdrawForm.data.password}
                                    onChange={(e) => withdrawForm.setData('password', e.target.value)}
                                    placeholder={t('service_user.enter_wallet_password')}
                                />
                                <InputError message={withdrawForm.errors.password} />
                            </div>
                            <Button type="submit" disabled={withdrawForm.processing}>
                                {withdrawForm.processing
                                    ? t('common.processing')
                                    : t('wallet.withdraw')}
                            </Button>
                        </form>
                    )}

                    {activeTab === 'password' && (
                        <form onSubmit={handleChangePassword} className="space-y-4">
                            {walletHasPassword && (
                                <div className="space-y-2">
                                    <Label htmlFor="current-password">
                                        {t('service_user.current_password')}
                                    </Label>
                                    <Input
                                        id="current-password"
                                        type="password"
                                        value={passwordForm.data.current_password}
                                        onChange={(e) =>
                                            passwordForm.setData('current_password', e.target.value)
                                        }
                                        required
                                        placeholder={t('service_user.enter_current_password')}
                                    />
                                    <InputError message={passwordForm.errors.current_password} />
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="new-password">
                                    {t('wallet.new_password')}
                                </Label>
                                <Input
                                    id="new-password"
                                    type="password"
                                    value={passwordForm.data.new_password}
                                    onChange={(e) => passwordForm.setData('new_password', e.target.value)}
                                    placeholder={t('service_user.enter_new_password')}
                                    required
                                    minLength={6}
                                />
                                <InputError message={passwordForm.errors.new_password} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="confirm-password">
                                    {t('service_user.confirm_password')}
                                </Label>
                                <Input
                                    id="confirm-password"
                                    type="password"
                                    value={passwordForm.data.confirm_password}
                                    onChange={(e) =>
                                        passwordForm.setData('confirm_password', e.target.value)
                                    }
                                    placeholder={t('service_user.enter_confirm_password')}
                                    required
                                    minLength={6}
                                />
                                <InputError message={passwordForm.errors.confirm_password} />
                            </div>
                            <Button type="submit" disabled={passwordForm.processing}>
                                {passwordForm.processing
                                    ? t('common.processing')
                                    : t('service_user.change_password')}
                            </Button>
                        </form>
                    )}
                </div>
            </CardContent>
        </Card>
    );
};

export default WalletActionsTabs;


