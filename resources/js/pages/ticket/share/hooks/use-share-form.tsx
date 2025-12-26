import { useForm, router } from '@inertiajs/react';
import { ticket_share_store } from '@/routes';
import { useTranslation } from 'react-i18next';

export const useShareForm = () => {
    const { t } = useTranslation();
    const form = useForm({
        platform: '', // Platform type: 1 = GOOGLE, 2 = META
        account_id: '', // Selected account ID
        bm_bc_mcc_id: '', // BM/BC/MCC ID
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!form.data.platform) {
            form.setError('platform', t('ticket.share.platform_required', { defaultValue: 'Vui lòng chọn kênh quảng cáo' }));
            return;
        }

        if (!form.data.account_id) {
            form.setError('account_id', t('ticket.share.account_id_required', { defaultValue: 'Vui lòng chọn tài khoản' }));
            return;
        }

        if (!form.data.bm_bc_mcc_id || form.data.bm_bc_mcc_id.trim() === '') {
            form.setError('bm_bc_mcc_id', t('ticket.share.bm_bc_mcc_id_required', { defaultValue: 'Vui lòng nhập ID BM/MCC' }));
            return;
        }

        if (!form.data.notes || form.data.notes.trim() === '') {
            form.setError('notes', t('ticket.share.notes_required', { defaultValue: 'Vui lòng nhập ghi chú' }));
            return;
        }

        const platformValue = parseInt(form.data.platform);
        
        const submitData = {
            platform: platformValue,
            account_id: form.data.account_id,
            bm_bc_mcc_id: form.data.bm_bc_mcc_id.trim(),
            notes: form.data.notes.trim(),
        };

        router.post(ticket_share_store().url, submitData, {
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

