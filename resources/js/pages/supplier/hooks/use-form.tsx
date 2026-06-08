import { DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE as DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE_IMPORT } from '@/pages/service-package/hooks/use-form';
import { CreateSupplierForm, SupplierItem } from '@/pages/supplier/types/type';
import { suppliers_create, suppliers_update } from '@/routes';
import { useForm } from '@inertiajs/react';
import React from 'react';

export const DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE =
    DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE_IMPORT;

export const useFormCreateSupplier = () => {
    const form = useForm<CreateSupplierForm>({
        name: '',
        open_fee: '0',
        supplier_fee_percent: '0',
        monthly_spending_fee_structure: [],
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
        open_fee: item.open_fee
            ? parseFloat(Number(item.open_fee).toFixed(2)).toString()
            : '0',
        supplier_fee_percent: item.supplier_fee_percent
            ? parseFloat(
                  Number(item.supplier_fee_percent).toFixed(2),
              ).toString()
            : '0',
        monthly_spending_fee_structure:
            item.monthly_spending_fee_structure ?? [],
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
