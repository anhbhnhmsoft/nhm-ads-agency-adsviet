import { useForm, router } from '@inertiajs/react';
import { ticket_refund_store } from '@/routes';
import { useTranslation } from 'react-i18next';

export const useRefundForm = () => {
    const { t } = useTranslation();
    const form = useForm({
        platform: '', // Platform type: 1 = GOOGLE, 2 = META
        account_id: '', // Currently selected account for adding
        account_ids: [] as string[], // Array of selected account IDs
        liquidation_type: 'withdraw_to_wallet',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!form.data.account_ids || form.data.account_ids.length === 0) {
            form.setError('account_ids', t('ticket.refund.account_ids_required', { defaultValue: 'Vui lòng chọn ít nhất một tài khoản' }));
            return;
        }

        if (!form.data.notes || form.data.notes.trim() === '') {
            form.setError('notes', t('ticket.refund.notes_required', { defaultValue: 'Vui lòng nhập ghi chú' }));
            return;
        }

        const platformValue = form.data.platform ? parseInt(form.data.platform) : null;
        
        const submitData = {
            platform: platformValue,
            account_ids: form.data.account_ids,
            liquidation_type: form.data.liquidation_type,
            notes: form.data.notes.trim(),
        };

        router.post(ticket_refund_store().url, submitData, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            },
            onError: (errors) => {
                Object.keys(errors).forEach((key) => {
                    form.setError(key as keyof typeof form.data, errors[key] as string);
                });
            },
        });
    };

    return {
        form,
        handleSubmit,
    };
};

