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
    timezone_bm?: string;
    payment_type?: 'prepay' | 'postpay';
    asset_access?: 'full_asset' | 'basic_asset';
};

export const useServicePurchaseForm = () => {
    const form = useForm<ServicePurchaseFormData>({
        package_id: '',
        top_up_amount: '',
        budget: '0',
        info_fanpage: '',
        info_website: '',
        asset_access: 'full_asset',
    });

    const submit = (
        packageId: string,
        topUpAmount: string,
        metaEmail?: string,
        displayName?: string,
        timezoneBm?: string,
        budget?: string,
        bmMccConfig?: {
            bm_id?: string;
            info_fanpage?: string;
            info_website?: string;
            payment_type?: 'prepay' | 'postpay';
            asset_access?: 'full_asset' | 'basic_asset';
        },
        onSuccess?: () => void
    ) => {
        const payload: ServicePurchaseFormData = {
            package_id: packageId,
            top_up_amount: topUpAmount,
            budget: budget || '0',
            meta_email: metaEmail || '',
            display_name: displayName || '',
            timezone_bm: timezoneBm || '',
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

