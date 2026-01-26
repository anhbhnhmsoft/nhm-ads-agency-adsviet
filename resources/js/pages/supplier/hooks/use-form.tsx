import {
    CreateSupplierForm,
    SupplierItem,
} from '@/pages/supplier/types/type';
import { DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE as DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE_IMPORT } from '@/pages/service-package/hooks/use-form';
import { useForm } from '@inertiajs/react';
import React from 'react';
import { suppliers_create, suppliers_update } from '@/routes';

export const DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE = DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE_IMPORT;

export const useFormCreateSupplier = () => {
    const form = useForm<CreateSupplierForm>({
        name: '',
        open_fee: '0',
        supplier_fee_percent: '0',
        monthly_spending_fee_structure: DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE,
        disabled: false,
    });

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.post(suppliers_create().url);
    };

    return {
        form,
        submit,
    };
};

export const useFormEditSupplier = (id: string, item: SupplierItem) => {
    const form = useForm<CreateSupplierForm>({
        name: item.name,
        open_fee: item.open_fee,
        supplier_fee_percent: item.supplier_fee_percent || '0',
        monthly_spending_fee_structure:
            (item.monthly_spending_fee_structure &&
                item.monthly_spending_fee_structure.length > 0
                ? item.monthly_spending_fee_structure
                : DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE),
        disabled: item.disabled,
    });

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.put(suppliers_update(id).url);
    };

    return {
        form,
        submit,
    };
};

