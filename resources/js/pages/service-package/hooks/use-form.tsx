import { _PlatformType } from '@/lib/types/constants';
import { CreateServicePackageForm, ServicePackageItem } from '@/pages/service-package/types/type';
import { useForm } from '@inertiajs/react';
import React from 'react';
import { service_packages_create, service_packages_update } from '@/routes';

export const useFormCreateServicePackage = () => {
    const form = useForm<CreateServicePackageForm>({
        name: '',
        description: null,
        platform: _PlatformType.META,
        features: [],
        open_fee: '0',
        range_min_top_up: '0',
        top_up_fee: '0',
        set_up_time: '0',
        disabled: false,
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

export const useFormEditServicePackage = (id: string, item: ServicePackageItem) => {
    const form = useForm<CreateServicePackageForm>({
        name: item.name,
        description: item.description,
        platform: item.platform,
        features: item.features,
        open_fee: item.open_fee,
        range_min_top_up: item.range_min_top_up,
        top_up_fee: item.top_up_fee,
        set_up_time: item.set_up_time.toString(),
        disabled: item.disabled,
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
