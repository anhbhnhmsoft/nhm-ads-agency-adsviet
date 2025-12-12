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

type NetworkType = 'TRC20' | 'BEP20' | '';

export default function TicketDepositApp() {
    const { t } = useTranslation();

    const form = useForm({
        amount: '',
        network: '' as NetworkType,
        note: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const amountNumber = Number(form.data.amount);
        if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
            toast.error(t('ticket.deposit_app.amount_label'));
            return;
        }
        if (!form.data.network) {
            toast.error(t('ticket.deposit_app.network_placeholder'));
            return;
        }
        const note = form.data.note.trim();

        const description = note || '';

        router.post(
            ticket_store().url,
            {
                subject: t('ticket.deposit_app.title'),
                description,
                priority: _TicketPriority.HIGH,
                metadata: {
                    type: 'wallet_deposit_app',
                    amount: amountNumber,
                    network: form.data.network,
                    notes: note || null,
                },
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(t('service_management.support_request_success'));
                    form.reset();
                },
                onError: (errors) => {
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
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="amount">{t('ticket.deposit_app.amount_label')}</Label>
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
                                    <Label htmlFor="network">{t('ticket.deposit_app.network_label')}</Label>
                                    <Select
                                        value={form.data.network || undefined}
                                        onValueChange={(value: NetworkType) => form.setData('network', value)}
                                    >
                                        <SelectTrigger id="network">
                                            <SelectValue placeholder={t('ticket.deposit_app.network_placeholder')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="TRC20">TRC20</SelectItem>
                                            <SelectItem value="BEP20">BEP20</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="note">{t('ticket.deposit_app.note_label')}</Label>
                                <Textarea
                                    id="note"
                                    value={form.data.note}
                                    onChange={(e) => form.setData('note', e.target.value)}
                                    placeholder={t('ticket.deposit_app.note_placeholder')}
                                />
                            </div>

                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? t('common.loading') : t('ticket.deposit_app.submit')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

