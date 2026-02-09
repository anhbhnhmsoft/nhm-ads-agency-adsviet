import { useForm } from '@inertiajs/react';
import { CreateCommissionForm } from '../types/type';

export const useFormCreateCommission = () => {
    const form = useForm<CreateCommissionForm>({
        service_package_id: '',
        type: 'service',
        rate: '0',
        min_amount: '',
        max_amount: '',
        is_active: true,
        description: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/commissions/create', {
            preserveScroll: true,
        });
    };

    return { form, submit };
};

export const useFormUpdateCommission = (initialData: CreateCommissionForm) => {
    const form = useForm<CreateCommissionForm>(initialData);

    const submit = (id: string) => (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/commissions/${id}`, {
            preserveScroll: true,
        });
    };

    return { form, submit };
};


