import { useForm } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import React from 'react';
import { service_purchase_purchase } from '@/routes';

export type AccountFormData = {
    meta_email?: string;
    display_name?: string;
    bm_ids?: string[];
    fanpages?: string[];
    websites?: string[];
    timezone_bm?: string;
    asset_access?: 'full_asset' | 'basic_asset';
};

type ServicePurchaseFormData = {
    package_id: string;
    top_up_amount: string;
    budget: string;
    payment_type?: 'prepay' | 'postpay';
    meta_email?: string;
    display_name?: string;
    bm_id?: string;
    info_fanpage?: string;
    info_website?: string;
    timezone_bm?: string;
    asset_access?: 'full_asset' | 'basic_asset';
    accounts?: AccountFormData[];
};

export const useServicePurchaseForm = () => {
    const page = usePage();
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
            postpay_days?: number;
            asset_access?: 'full_asset' | 'basic_asset';
        },
        accounts?: AccountFormData[],
        onSuccess?: () => void
    ) => {
        const payload: ServicePurchaseFormData = {
            package_id: packageId,
            top_up_amount: topUpAmount,
            budget: budget || '0',
        };

        if (accounts && accounts.length > 0) {
            const filteredAccounts = accounts
                .filter(acc => acc.meta_email || acc.display_name || (acc.bm_ids && acc.bm_ids.length > 0))
                .map(acc => ({
                    ...acc,
                    bm_ids: acc.bm_ids?.filter(bm => bm?.trim()) || [],
                    fanpages: acc.fanpages?.filter(fp => fp?.trim()) || [],
                    websites: acc.websites?.filter(ws => ws?.trim()) || [],
                }));
            
            if (filteredAccounts.length > 0) {
                payload.accounts = filteredAccounts;
            }
        } else {
            payload.meta_email = metaEmail || '';
            payload.display_name = displayName || '';
            payload.timezone_bm = timezoneBm || '';
            if (bmMccConfig) {
                Object.assign(payload, bmMccConfig);
            }
        }

        // Payment type luôn có
        if (bmMccConfig?.payment_type) {
            payload.payment_type = bmMccConfig.payment_type;
        }

        form.transform(() => payload);
        
        // Log để debug
        const currentLocale = (page.props as any)?.locale || 'unknown';
        console.log('[Frontend] ServicePurchaseForm submit - Locale Debug', {
            current_locale: currentLocale,
            payload: payload,
            url: service_purchase_purchase().url,
        });
        
        form.post(service_purchase_purchase().url, {
            onSuccess: () => {
                form.reset();
                onSuccess?.();
            },
            onError: (errors) => {
                const currentLocale = (page.props as any)?.locale || 'unknown';
                console.log('[Frontend] ServicePurchaseForm errors received', {
                    errors: errors,
                    current_locale: currentLocale,
                    error_keys: Object.keys(errors),
                    meta_email_error: errors.meta_email || errors['accounts.0.meta_email'],
                });
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

