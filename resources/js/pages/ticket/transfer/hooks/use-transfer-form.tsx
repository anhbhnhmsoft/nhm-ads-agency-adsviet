import { useForm } from '@inertiajs/react';
import { ticket_transfer_store } from '@/routes';

export const useTransferForm = () => {
    const form = useForm({
        platform: '', // Platform type: 1 = GOOGLE, 2 = META
        from_account_id: '',
        to_account_id: '',
        amount: '',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(ticket_transfer_store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            },
        });
    };

    return {
        form,
        handleSubmit,
    };
};

