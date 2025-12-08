import { useForm, router } from '@inertiajs/react';
import { ticket_transfer_store } from '@/routes';
import { useTranslation } from 'react-i18next';

export const useTransferForm = () => {
    const { t } = useTranslation();
    const form = useForm({
        platform: '', // Platform type: 1 = GOOGLE, 2 = META
        from_account_id: '',
        to_account_id: '',
        amount: '',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!form.data.platform) {
            form.setError('platform', t('ticket.transfer.platform_required', { defaultValue: 'Vui lòng chọn kênh quảng cáo' }));
            return;
        }

        if (!form.data.from_account_id) {
            form.setError('from_account_id', t('ticket.transfer.from_account_required', { defaultValue: 'Vui lòng chọn tài khoản nguồn' }));
            return;
        }

        if (!form.data.to_account_id) {
            form.setError('to_account_id', t('ticket.transfer.to_account_required', { defaultValue: 'Vui lòng chọn tài khoản đích' }));
            return;
        }

        if (form.data.from_account_id === form.data.to_account_id) {
            form.setError('to_account_id', t('ticket.transfer.accounts_must_different', { defaultValue: 'Tài khoản nguồn và tài khoản đích không được trùng nhau' }));
            return;
        }

        if (!form.data.amount || parseFloat(form.data.amount) <= 0) {
            form.setError('amount', t('ticket.transfer.amount_required', { defaultValue: 'Vui lòng nhập số tiền' }));
            return;
        }

        if (!form.data.notes || form.data.notes.trim() === '') {
            form.setError('notes', t('ticket.transfer.notes_required', { defaultValue: 'Vui lòng nhập ghi chú' }));
            return;
        }
        
        const platformValue = parseInt(form.data.platform);
        
        const submitData = {
            platform: platformValue,
            from_account_id: form.data.from_account_id,
            to_account_id: form.data.to_account_id,
            amount: form.data.amount,
            notes: form.data.notes.trim(),
        };

        router.post(ticket_transfer_store().url, submitData, {
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

