import { useForm } from '@inertiajs/react';
import React from 'react';
import { ticket_create_account_store } from '@/routes';
import type { CreateAccountFormData } from '../types/type';

export const useCreateAccountForm = () => {
    const form = useForm<CreateAccountFormData>({
        package_id: '',
        budget: '0',
        asset_access: 'full_asset',
        notes: '',
    });

    const submit = (
        packageId: string,
        budget: string,
        accounts?: Array<{
            meta_email?: string;
            display_name?: string;
            bm_ids?: string[];
            fanpages?: string[];
            websites?: string[];
            timezone_bm?: string;
            asset_access?: 'full_asset' | 'basic_asset';
        }>,
        bmMccConfig?: {
            bm_id?: string;
            info_fanpage?: string;
            info_website?: string;
            asset_access?: 'full_asset' | 'basic_asset';
        },
        notes?: string,
        onSuccess?: () => void
    ) => {
        const payload: CreateAccountFormData = {
            package_id: packageId,
            budget: budget || '0',
            notes: notes || '',
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
        }

        if (bmMccConfig) {
            Object.assign(payload, bmMccConfig);
        }

        form.transform(() => payload);
        form.post(ticket_create_account_store().url, {
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
