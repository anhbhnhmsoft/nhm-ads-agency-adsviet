import { useState } from 'react';
import { router } from '@inertiajs/react';
import { wallet_deposit_cancel } from '@/routes';
import { useTranslation } from 'react-i18next';

export function useCancelDeposit() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const { t } = useTranslation();

    const cancelDeposit = (transactionId: string | number, onSuccess?: () => void) => {
        if (loading) return;

        setLoading(true);
        setError(null);

        router.post(
            wallet_deposit_cancel(transactionId).url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setError(null);
                    router.reload({ only: ['pending_deposit', 'wallet'] });
                    onSuccess?.();
                },
                onError: (errors) => {
                    const errorMessage = 
                        errors?.error || 
                        errors?.message || 
                        (typeof errors === 'string' ? errors :  t('wallet.generic_error'));
                    setError(errorMessage);
                },
                onFinish: () => {
                    setLoading(false);
                },
            }
        );
    };

    return {
        cancelDeposit,
        loading,
        error,
    };
}

