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
import { ticket_store } from '@/routes';
import { _TicketPriority } from '@/pages/ticket/types/constants';
import { useMemo, useState } from 'react';
import { TRANSFER_PLATFORM_META, TRANSFER_PLATFORM_GOOGLE } from './transfer/types/constants';

type DepositAppPageProps = {
    accounts: Array<{
        id: string;
        account_id: string;
        account_name: string;
        platform: number;
    }>;
};

export default function TicketDepositApp({ accounts }: DepositAppPageProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const form = useForm({
        platform: '',
        account_id: '',
        amount: '',
        note: '',
    });

    // Filter accounts by selected platform
    const filteredAccounts = useMemo(() => {
        if (!form.data.platform) {
            return [];
        }
        const platformNum = parseInt(form.data.platform);
        return accounts.filter(acc => acc.platform === platformNum);
    }, [accounts, form.data.platform]);

    // Reset account selection when platform changes
    const handlePlatformChange = (value: string) => {
        form.setData({
            ...form.data,
            platform: value,
            account_id: '',
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Prevent double submission
        if (isSubmitting || form.processing) {
            return;
        }
        
        if (!form.data.platform) {
            toast.error(t('ticket.transfer.platform_required', { defaultValue: 'Vui lòng chọn kênh quảng cáo' }));
            return;
        }

        if (!form.data.account_id) {
            toast.error(t('ticket.deposit_app.account_required', { defaultValue: 'Vui lòng chọn tài khoản' }));
            return;
        }

        const amountNumber = Number(form.data.amount);
        if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
            toast.error(t('ticket.deposit_app.amount_invalid', { defaultValue: 'Số tiền không hợp lệ' }));
            return;
        }

        const note = form.data.note.trim();
        const description = note || '';

        // Find account name
        const selectedAccount = filteredAccounts.find(acc => acc.account_id === form.data.account_id);
        const accountName = selectedAccount?.account_name || form.data.account_id;

        // Set submitting state immediately to prevent double clicks
        setIsSubmitting(true);

        router.post(
            ticket_store().url,
            {
                subject: t('ticket.deposit_app.title'),
                description,
                priority: _TicketPriority.HIGH,
                metadata: {
                    type: 'wallet_deposit_app',
                    platform: parseInt(form.data.platform),
                    account_id: form.data.account_id,
                    account_name: accountName,
                    amount: amountNumber,
                    notes: note || null,
                },
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(t('service_management.support_request_success'));
                    form.reset();
                    setIsSubmitting(false);
                },
                onError: (errors) => {
                    setIsSubmitting(false);
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
                onFinish: () => {
                    // Ensure isSubmitting is reset even if something goes wrong
                    setIsSubmitting(false);
                },
            },
        );
    };

    return (
        <AppLayout>
            <Head title={t('ticket.deposit_app.title')} />
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('ticket.deposit_app.title')}</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {t('ticket.deposit_app.description')}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-4" onSubmit={handleSubmit}>
                            {/* Platform Selection */}
                            <div className="space-y-2">
                                <Label htmlFor="platform">
                                    {t('ticket.transfer.platform', { defaultValue: 'Kênh quảng cáo' })}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={form.data.platform}
                                    onValueChange={handlePlatformChange}
                                >
                                    <SelectTrigger id="platform">
                                        <SelectValue placeholder={t('ticket.transfer.select_platform', { defaultValue: 'Chọn kênh quảng cáo' })} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={String(TRANSFER_PLATFORM_META)}>
                                            {t('enum.platform_type.meta', { defaultValue: 'Meta Ads' })}
                                        </SelectItem>
                                        <SelectItem value={String(TRANSFER_PLATFORM_GOOGLE)}>
                                            {t('enum.platform_type.google', { defaultValue: 'Google Ads' })}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {form.errors.platform && (
                                    <p className="text-sm text-red-500">{form.errors.platform}</p>
                                )}
                            </div>

                            {form.data.platform && (
                                <div className="space-y-2">
                                    <Label htmlFor="account_id">
                                        {t('ticket.deposit_app.account_label', { defaultValue: 'Tài khoản cần nạp tiền' })}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={form.data.account_id}
                                        onValueChange={(value) => form.setData('account_id', value)}
                                        disabled={!form.data.platform || filteredAccounts.length === 0}
                                    >
                                        <SelectTrigger id="account_id">
                                            <SelectValue placeholder={t('ticket.transfer.select_account', { defaultValue: 'Chọn tài khoản' })}>
                                                {form.data.account_id
                                                    ? (() => {
                                                        const selected = filteredAccounts.find(acc => acc.account_id === form.data.account_id);
                                                        return selected 
                                                            ? `${selected.account_name} (${selected.account_id})`
                                                            : form.data.account_id;
                                                    })()
                                                    : null}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredAccounts.map((account) => (
                                                <SelectItem key={account.id} value={account.account_id}>
                                                    <span>{account.account_name} ({account.account_id})</span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.account_id && (
                                        <p className="text-sm text-red-500">{form.errors.account_id}</p>
                                    )}
                                    {form.data.platform && filteredAccounts.length === 0 && (
                                        <p className="text-sm text-yellow-600">
                                            {t('ticket.transfer.no_accounts', { defaultValue: 'Không có tài khoản nào cho kênh quảng cáo này' })}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="amount">
                                    {t('ticket.deposit_app.amount_label', { defaultValue: 'Số tiền nạp' })} (USD)
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={form.data.amount}
                                    onChange={(e) => form.setData('amount', e.target.value)}
                                    placeholder="0.00"
                                />
                                {form.errors.amount && (
                                    <p className="text-sm text-red-500">{form.errors.amount}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="note">{t('ticket.deposit_app.note_label', { defaultValue: 'Ghi chú (tùy chọn)' })}</Label>
                                <Textarea
                                    id="note"
                                    value={form.data.note}
                                    onChange={(e) => form.setData('note', e.target.value)}
                                    placeholder={t('ticket.deposit_app.note_placeholder', { defaultValue: 'Nhập ghi chú cho yêu cầu nạp tiền' })}
                                />
                            </div>

                            <Button type="submit" disabled={form.processing || isSubmitting}>
                                {form.processing || isSubmitting ? t('common.loading') : t('ticket.deposit_app.submit', { defaultValue: 'Tạo yêu cầu nạp' })}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

