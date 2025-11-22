import { useForm } from '@inertiajs/react';
import React from 'react';
import { service_purchase_purchase } from '@/routes';

type ServicePurchaseFormData = {
    package_id: string;
    top_up_amount: string;
    meta_email?: string;
    display_name?: string;
};

export const useServicePurchaseForm = () => {
    const form = useForm<ServicePurchaseFormData>({
        package_id: '',
        top_up_amount: '',
    });

    const submit = (packageId: string, topUpAmount: string, metaEmail?: string, displayName?: string, onSuccess?: () => void) => {
        const payload: ServicePurchaseFormData = {
            package_id: packageId,
            top_up_amount: topUpAmount,
            meta_email: metaEmail || '',
            display_name: displayName || '',
        };

        form.transform(() => payload);
        form.post(service_purchase_purchase().url, {
            onSuccess: () => {
                form.reset();
                onSuccess?.();
            },
        });
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
    };

    return {
        form,
        submit,
        handleSubmit,
    };
};

