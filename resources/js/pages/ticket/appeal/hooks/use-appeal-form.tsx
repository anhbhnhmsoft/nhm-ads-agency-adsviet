import { ticket_appeal_store } from '@/routes';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export const useAppealForm = () => {
    const { t } = useTranslation();
    const form = useForm({
        platform: '', // Platform type: 1 = GOOGLE, 2 = META
        account_id: '', // Selected account ID
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (form.processing) {
            return;
        }

        if (!form.data.platform) {
            form.setError(
                'platform',
                t('ticket.appeal.platform_required', {
                    defaultValue: 'Vui lòng chọn kênh quảng cáo',
                }),
            );
            return;
        }

        if (!form.data.account_id) {
            form.setError(
                'account_id',
                t('ticket.appeal.account_id_required', {
                    defaultValue: 'Vui lòng chọn tài khoản',
                }),
            );
            return;
        }

        if (!form.data.notes || form.data.notes.trim() === '') {
            form.setError(
                'notes',
                t('ticket.appeal.notes_required', {
                    defaultValue: 'Vui lòng nhập ghi chú',
                }),
            );
            return;
        }

        const platformValue = parseInt(form.data.platform);

        const submitData = {
            platform: platformValue,
            account_id: form.data.account_id,
            notes: form.data.notes.trim(),
        };

        form.transform(() => submitData);
        form.post(ticket_appeal_store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            },
            onError: (errors: Record<string, string>) => {
                Object.keys(errors).forEach((key) => {
                    form.setError(
                        key as keyof typeof form.data,
                        errors[key] as string,
                    );
                });
            },
        });
    };

    return {
        form,
        handleSubmit,
    };
};
