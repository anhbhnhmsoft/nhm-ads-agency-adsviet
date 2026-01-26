import { _PlatformType } from '@/lib/types/constants';
import {
    CreateServicePackageForm,
    MonthlySpendingFeeItem,
    ServicePackageItem,
} from '@/pages/service-package/types/type';
import { useForm } from '@inertiajs/react';
import React from 'react';
import { service_packages_create, service_packages_update } from '@/routes';

export const DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE: MonthlySpendingFeeItem[] = [
    { range: '$10,000 – $50,000', fee_percent: '5%' },
    { range: '$50,000 – $100,000', fee_percent: '4.5%' },
    { range: '$100,000 – $300,000', fee_percent: '4%' },
    { range: '$300,000 – $500,000', fee_percent: '3.5%' },
    { range: '$500,000 – $1,000,000', fee_percent: '3%' },
    { range: '$1,000,000 – $2,000,000', fee_percent: '2.5%' },
    { range: '$2,000,000 – $10,000,000', fee_percent: '2%' },
];

export const useFormCreateServicePackage = () => {
    const form = useForm<CreateServicePackageForm>({
        name: '',
        description: null,
        platform: _PlatformType.META,
        features: [],
        open_fee: '0',
        range_min_top_up: '0',
        top_up_fee: '0',
        supplier_fee_percent: '0',
        supplier_id: null,
        set_up_time: '0',
        disabled: false,
        monthly_spending_fee_structure: DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE,
        postpay_user_ids: [],
    });

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.post(service_packages_create().url)
    };

    return {
        form,
        submit,
    };
};

export const useFormEditServicePackage = (id: string, item: ServicePackageItem, postpayUserIds: string[] = []) => {
    const form = useForm<CreateServicePackageForm & { postpay_user_ids?: string[] }>({
        name: item.name,
        description: item.description,
        platform: item.platform,
        features: item.features,
        open_fee: item.open_fee,
        range_min_top_up: item.range_min_top_up,
        top_up_fee: item.top_up_fee,
        supplier_fee_percent: item.supplier_fee_percent || '0',
        supplier_id: item.supplier_id || null,
        set_up_time: item.set_up_time.toString(),
        disabled: item.disabled,
        monthly_spending_fee_structure:
            (item.monthly_spending_fee_structure &&
                item.monthly_spending_fee_structure.length > 0
                ? item.monthly_spending_fee_structure
                : DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE),
        postpay_user_ids: postpayUserIds,
    });

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.put(service_packages_update(id).url);
    };

    return {
        form,
        submit,
    };
}
