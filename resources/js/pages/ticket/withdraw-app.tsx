import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { ticket_store, ticket_withdraw_app } from '@/routes';
import { _TicketPriority } from '@/pages/ticket/types/constants';

type WithdrawType = 'bank' | 'usdt';
type NetworkType = 'TRC20' | 'BEP20' | '';

export default function TicketWithdrawApp() {
    const { t } = useTranslation();

    const form = useForm({
        amount: '',
        withdraw_type: 'bank' as WithdrawType,
        bank_name: '',
        account_holder: '',
        account_number: '',
        crypto_address: '',
        network: '' as NetworkType,
        password: '',
        note: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const amountNumber = Number(form.data.amount);
        if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
            toast.error(t('wallet.validation.amount_required'));
            return;
        }
        if (!form.data.password.trim()) {
            toast.error(t('wallet.validation.password_required'));
            return;
        }

        const withdrawType = form.data.withdraw_type;
        let withdrawInfo: Record<string, any> = {};
        if (withdrawType === 'bank') {
            if (!form.data.bank_name.trim() || !form.data.account_holder.trim() || !form.data.account_number.trim()) {
                toast.error(t('wallet.validation.bank_info_required'));
                return;
            }
            withdrawInfo = {
                bank_name: form.data.bank_name.trim(),
                account_holder: form.data.account_holder.trim(),
                account_number: form.data.account_number.trim(),
            };
        } else {
            if (!form.data.crypto_address.trim() || !form.data.network) {
                toast.error(t('wallet.validation.crypto_info_required'));
                return;
            }
            withdrawInfo = {
                crypto_address: form.data.crypto_address.trim(),
                network: form.data.network,
            };
        }

        const subject = t('ticket.withdraw_app.title');
        const note = form.data.note.trim();
        const description = note || '';

        router.post(
            ticket_store().url,
            {
                subject,
                description,
                priority: _TicketPriority.HIGH,
                metadata: {
                    type: 'wallet_withdraw_app',
                    amount: amountNumber,
                    withdraw_type: withdrawType,
                    withdraw_info: withdrawInfo,
                    notes: note || null,
                },
                wallet_password: form.data.password,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(t('service_management.support_request_success'));
                    form.reset();
                    form.setData('withdraw_type', 'bank');
                },
                onError: (errors) => {
                    if (errors.error) {
                        toast.error(errors.error as string);
                        return;
                    }
                    toast.error(t('common.error'));
                },
            },
        );
    };

    return (
        <AppLayout>
            <Head title={t('ticket.withdraw_app.title')} />
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('ticket.withdraw_app.title')}</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {t('ticket.withdraw_app.description')}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-4" onSubmit={handleSubmit}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="amount">{t('wallet.amount')}</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.data.amount}
                                        onChange={(e) => form.setData('amount', e.target.value)}
                                        placeholder="0.00"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="withdraw_type">{t('ticket.withdraw_app.method_label')}</Label>
                                    <Select
                                        value={form.data.withdraw_type}
                                        onValueChange={(value: WithdrawType) => {
                                            form.setData('withdraw_type', value);
                                            if (value === 'bank') {
                                                form.setData('crypto_address', '');
                                                form.setData('network', '');
                                            } else {
                                                form.setData('bank_name', '');
                                                form.setData('account_holder', '');
                                                form.setData('account_number', '');
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="withdraw_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="bank">{t('wallet.withdraw_via_bank')}</SelectItem>
                                            <SelectItem value="usdt">{t('wallet.withdraw_via_usdt')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {form.data.withdraw_type === 'bank' ? (
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="bank_name">{t('wallet.bank_name')}</Label>
                                        <Input
                                            id="bank_name"
                                            value={form.data.bank_name}
                                            onChange={(e) => form.setData('bank_name', e.target.value)}
                                            placeholder={t('wallet.enter_bank_name')}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="account_holder">{t('wallet.account_holder')}</Label>
                                        <Input
                                            id="account_holder"
                                            value={form.data.account_holder}
                                            onChange={(e) => form.setData('account_holder', e.target.value)}
                                            placeholder={t('wallet.enter_account_holder')}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="account_number">{t('wallet.account_number')}</Label>
                                        <Input
                                            id="account_number"
                                            value={form.data.account_number}
                                            onChange={(e) => form.setData('account_number', e.target.value)}
                                            placeholder={t('wallet.enter_account_number')}
                                        />
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="crypto_address">{t('wallet.crypto_address')}</Label>
                                        <Input
                                            id="crypto_address"
                                            value={form.data.crypto_address}
                                            onChange={(e) => form.setData('crypto_address', e.target.value)}
                                            placeholder={t('wallet.enter_crypto_address')}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="network">{t('wallet.select_network')}</Label>
                                        <Select
                                            value={form.data.network || undefined}
                                            onValueChange={(value: NetworkType) => form.setData('network', value)}
                                        >
                                            <SelectTrigger id="network">
                                                <SelectValue placeholder={t('wallet.select_network_placeholder')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="TRC20">TRC20</SelectItem>
                                                <SelectItem value="BEP20">BEP20</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="password">{t('wallet.password')}</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="note">{t('ticket.withdraw_app.note_label')}</Label>
                                <Textarea
                                    id="note"
                                    value={form.data.note}
                                    onChange={(e) => form.setData('note', e.target.value)}
                                    placeholder={t('ticket.withdraw_app.note_placeholder')}
                                />
                            </div>

                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? t('common.loading') : t('ticket.withdraw_app.submit')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

