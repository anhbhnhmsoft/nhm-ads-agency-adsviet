import { useForm } from '@inertiajs/react';
import React from 'react';
import { service_purchase_purchase } from '@/routes';

type ServicePurchaseFormData = {
    package_id: string;
    top_up_amount: string;
    budget: string;
    meta_email?: string;
    display_name?: string;
    bm_id?: string;
    info_fanpage?: string;
    info_website?: string;
    payment_type?: 'prepay' | 'postpay';
};

export const useServicePurchaseForm = () => {
    const form = useForm<ServicePurchaseFormData>({
        package_id: '',
        top_up_amount: '',
        budget: '0',
        info_fanpage: '',
        info_website: ''
    });

    const submit = (
        packageId: string, 
        topUpAmount: string, 
        metaEmail?: string, 
        displayName?: string, 
        budget?: string,
        bmMccConfig?: {
            bm_id?: string;
            info_fanpage?: string;
            info_website?: string;
            payment_type?: 'prepay' | 'postpay';
        },
        onSuccess?: () => void
    ) => {
        const payload: ServicePurchaseFormData = {
            package_id: packageId,
            top_up_amount: topUpAmount,
            budget: budget || '0',
            meta_email: metaEmail || '',
            display_name: displayName || '',
            ...(bmMccConfig || {}),
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

