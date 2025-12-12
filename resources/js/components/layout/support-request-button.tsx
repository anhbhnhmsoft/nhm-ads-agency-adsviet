import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select as UiSelect, SelectContent as UiSelectContent, SelectItem as UiSelectItem, SelectTrigger as UiSelectTrigger, SelectValue as UiSelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { ticket_store } from '@/routes';
import { _TicketPriority } from '@/pages/ticket/types/constants';
import type { TicketPriority } from '@/pages/ticket/types/type';

export function SupportRequestButton() {
    const { t } = useTranslation();
    const [supportDialogOpen, setSupportDialogOpen] = useState(false);
    const [supportType, setSupportType] = useState<'account_close' | 'account_appeal' | 'transfer_budget' | 'share_bm' | 'wallet_withdraw_app'>('account_close');
    const [supportNote, setSupportNote] = useState('');
    const [withdrawType, setWithdrawType] = useState<'bank' | 'usdt'>('bank');
    const [amount, setAmount] = useState('');
    const [bankName, setBankName] = useState('');
    const [accountHolder, setAccountHolder] = useState('');
    const [accountNumber, setAccountNumber] = useState('');
    const [cryptoAddress, setCryptoAddress] = useState('');
    const [network, setNetwork] = useState<'TRC20' | 'BEP20' | ''>('');
    const [walletPassword, setWalletPassword] = useState('');
    
    const supportForm = useForm<Record<string, any>>({
        subject: '',
        description: '',
        priority: _TicketPriority.HIGH as TicketPriority,
        metadata: null as any,
        wallet_password: '',
    });

    const handleSubmitSupportRequest = (e: React.FormEvent) => {
        e.preventDefault();

        const subjectMap: Record<typeof supportType, string> = {
            account_close: t('service_management.support_request_type_account_close'),
            account_appeal: t('service_management.support_request_type_account_appeal'),
            transfer_budget: t('service_management.support_request_type_transfer_budget'),
            share_bm: t('service_management.support_request_type_share_bm'),
            wallet_withdraw_app: t('service_management.support_request_type_wallet_withdraw_app'),
        };

        const subject = subjectMap[supportType] || t('service_management.support_request_title');
        const descriptionParts: string[] = [];
        descriptionParts.push(
            `${t('service_management.support_request_type_label')}: ${subject}`,
        );
        let metadata: any = null;

        if (supportType === 'wallet_withdraw_app') {
            const amountNumber = Number(amount);
            if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
                toast.error(t('wallet.validation.amount_required'));
                return;
            }
            if (!walletPassword.trim()) {
                toast.error(t('wallet.validation.password_required'));
                return;
            }

            if (withdrawType === 'bank') {
                if (!bankName.trim() || !accountHolder.trim() || !accountNumber.trim()) {
                    toast.error(t('wallet.validation.bank_info_required'));
                    return;
                }
            } else {
                if (!cryptoAddress.trim() || !network) {
                    toast.error(t('wallet.validation.crypto_info_required'));
                    return;
                }
            }

            metadata = {
                type: 'wallet_withdraw_app',
                amount: amountNumber,
                withdraw_type: withdrawType,
                withdraw_info:
                    withdrawType === 'bank'
                        ? {
                            bank_name: bankName.trim(),
                            account_holder: accountHolder.trim(),
                            account_number: accountNumber.trim(),
                        }
                        : {
                            crypto_address: cryptoAddress.trim(),
                            network,
                        },
            };

            descriptionParts.push(
                `${t('service_management.support_request_withdraw_amount_label')}: ${amountNumber} USDT`,
            );
        }

        if (supportNote.trim()) {
            descriptionParts.push(supportNote.trim());
        }
        const description = descriptionParts.join('\n\n');

        supportForm.transform(() => ({
            subject,
            description,
            priority: _TicketPriority.HIGH as TicketPriority,
            metadata,
            wallet_password: supportType === 'wallet_withdraw_app' ? walletPassword : undefined,
        }));

        supportForm.post(ticket_store().url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('service_management.support_request_success'));
                setSupportDialogOpen(false);
                setSupportNote('');
                setSupportType('account_close');
                setWithdrawType('bank');
                setAmount('');
                setBankName('');
                setAccountHolder('');
                setAccountNumber('');
                setCryptoAddress('');
                setNetwork('');
                setWalletPassword('');
                supportForm.reset();
            },
            onError: (errors: Record<string, string | string[]>) => {
                const first =
                    errors.error ??
                    errors.subject ??
                    errors.description ??
                    errors.metadata ??
                    null;
                if (!first) {
                    toast.error(t('common.error'));
                    return;
                }
                toast.error(Array.isArray(first) ? first[0] : first);
            },
        });
    };

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                className="bg-white text-primary"
                onClick={() => setSupportDialogOpen(true)}
            >
                {t('service_management.support_request_button')}
            </Button>
            <Dialog open={supportDialogOpen} onOpenChange={setSupportDialogOpen}>
                <DialogContent className="bg-white">
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.support_request_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('service_management.support_request_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form className="space-y-4 pt-2" onSubmit={handleSubmitSupportRequest}>
                        <div className="space-y-2">
                            <Label htmlFor="global-support-type">
                                {t('service_management.support_request_type_label')}
                            </Label>
                            <UiSelect
                                value={supportType}
                                onValueChange={(value: any) => setSupportType(value)}
                            >
                                <UiSelectTrigger id="global-support-type">
                                    <UiSelectValue />
                                </UiSelectTrigger>
                                <UiSelectContent>
                                    <UiSelectItem value="account_close">
                                        {t('service_management.support_request_type_account_close')}
                                    </UiSelectItem>
                                    <UiSelectItem value="account_appeal">
                                        {t('service_management.support_request_type_account_appeal')}
                                    </UiSelectItem>
                                    <UiSelectItem value="transfer_budget">
                                        {t('service_management.support_request_type_transfer_budget')}
                                    </UiSelectItem>
                                    <UiSelectItem value="share_bm">
                                        {t('service_management.support_request_type_share_bm')}
                                    </UiSelectItem>
                                    <UiSelectItem value="wallet_withdraw_app">
                                        {t('service_management.support_request_type_wallet_withdraw_app')}
                                    </UiSelectItem>
                                </UiSelectContent>
                            </UiSelect>
                        </div>
                        {supportType === 'wallet_withdraw_app' && (
                            <div className="space-y-3">
                                <div className="space-y-2">
                                    <Label htmlFor="withdraw-amount">
                                        {t('service_management.support_request_withdraw_amount_label')}
                                    </Label>
                                    <Input
                                        id="withdraw-amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        placeholder="0.00"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="withdraw-type">
                                        {t('service_management.support_request_withdraw_type_label')}
                                    </Label>
                                    <UiSelect
                                        value={withdrawType}
                                        onValueChange={(value: 'bank' | 'usdt') => {
                                            setWithdrawType(value);
                                            setBankName('');
                                            setAccountHolder('');
                                            setAccountNumber('');
                                            setCryptoAddress('');
                                            setNetwork('');
                                        }}
                                    >
                                        <UiSelectTrigger id="withdraw-type">
                                            <UiSelectValue />
                                        </UiSelectTrigger>
                                        <UiSelectContent>
                                            <UiSelectItem value="bank">
                                                {t('wallet.withdraw_via_bank')}
                                            </UiSelectItem>
                                            <UiSelectItem value="usdt">
                                                {t('wallet.withdraw_via_usdt')}
                                            </UiSelectItem>
                                        </UiSelectContent>
                                    </UiSelect>
                                </div>
                                {withdrawType === 'bank' ? (
                                    <div className="grid gap-3 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="withdraw-bank-name">
                                                {t('wallet.bank_name')}
                                            </Label>
                                            <Input
                                                id="withdraw-bank-name"
                                                value={bankName}
                                                onChange={(e) => setBankName(e.target.value)}
                                                placeholder={t('wallet.enter_bank_name')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="withdraw-account-holder">
                                                {t('wallet.account_holder')}
                                            </Label>
                                            <Input
                                                id="withdraw-account-holder"
                                                value={accountHolder}
                                                onChange={(e) => setAccountHolder(e.target.value)}
                                                placeholder={t('wallet.enter_account_holder')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="withdraw-account-number">
                                                {t('wallet.account_number')}
                                            </Label>
                                            <Input
                                                id="withdraw-account-number"
                                                value={accountNumber}
                                                onChange={(e) => setAccountNumber(e.target.value)}
                                                placeholder={t('wallet.enter_account_number')}
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="withdraw-crypto-address">
                                                {t('wallet.crypto_address')}
                                            </Label>
                                            <Input
                                                id="withdraw-crypto-address"
                                                value={cryptoAddress}
                                                onChange={(e) => setCryptoAddress(e.target.value)}
                                                placeholder={t('wallet.enter_crypto_address')}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="withdraw-network">
                                                {t('wallet.select_network')}
                                            </Label>
                                            <UiSelect
                                                value={network || undefined}
                                                onValueChange={(value: 'TRC20' | 'BEP20') => setNetwork(value)}
                                            >
                                                <UiSelectTrigger id="withdraw-network">
                                                    <UiSelectValue placeholder={t('wallet.select_network_placeholder')} />
                                                </UiSelectTrigger>
                                                <UiSelectContent>
                                                    <UiSelectItem value="TRC20">TRC20</UiSelectItem>
                                                    <UiSelectItem value="BEP20">BEP20</UiSelectItem>
                                                </UiSelectContent>
                                            </UiSelect>
                                        </div>
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <Label htmlFor="withdraw-wallet-password">
                                        {t('service_management.support_request_wallet_password_label')}
                                    </Label>
                                    <Input
                                        id="withdraw-wallet-password"
                                        type="password"
                                        value={walletPassword}
                                        onChange={(e) => setWalletPassword(e.target.value)}
                                        placeholder="••••••••"
                                    />
                                </div>
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="global-support-note">
                                {t('service_management.support_request_note_label')}
                            </Label>
                            <Textarea
                                id="global-support-note"
                                value={supportNote}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                                    setSupportNote(e.target.value)
                                }
                                placeholder={t(
                                    'service_management.support_request_note_placeholder',
                                )}
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setSupportDialogOpen(false)}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button 
                                type="submit" 
                                variant="outline" 
                                className="bg-white text-primary"
                                disabled={supportForm.processing}
                            >
                                {supportForm.processing 
                                    ? 'Đang gửi...' 
                                    : t('service_management.support_request_submit')
                                }
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

