import { useCallback, useState } from 'react';
import { useForm } from '@inertiajs/react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import { service_orders_approve } from '@/routes';

export const useServiceOrderAdminDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const form = useForm({
        meta_email: '',
        display_name: '',
        bm_id: '',
        info_fanpage: '',
        info_website: '',
        payment_type: '',
    });

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        const config = order.config_account || {};
        setSelectedOrder(order);
        form.setData({
            meta_email: (config.meta_email as string) || '',
            display_name: (config.display_name as string) || '',
            bm_id: (config.bm_id as string) || '',
            info_fanpage: (config.info_fanpage as string) || '',
            info_website: (config.info_website as string) || '',
            payment_type: (config.payment_type as string) || '',
        });
        form.clearErrors();
        setDialogOpen(true);
    }, [form]);

    const handleSubmitApprove = useCallback(() => {
        if (!selectedOrder) return;

        form.post(
            service_orders_approve({ id: selectedOrder.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    setSelectedOrder(null);
                    form.reset();
                    form.clearErrors();
                },
            },
        );
    }, [form, selectedOrder]);

    return {
        dialogOpen,
        setDialogOpen,
        metaEmail: form.data.meta_email,
        setMetaEmail: (value: string) => form.setData('meta_email', value),
        displayName: form.data.display_name,
        setDisplayName: (value: string) => form.setData('display_name', value),
        bmId: form.data.bm_id,
        setBmId: (value: string) => form.setData('bm_id', value),
        infoFanpage: form.data.info_fanpage,
        setInfoFanpage: (value: string) => form.setData('info_fanpage', value),
        infoWebsite: form.data.info_website,
        setInfoWebsite: (value: string) => form.setData('info_website', value),
        paymentType: form.data.payment_type,
        setPaymentType: (value: string) => form.setData('payment_type', value),
        formErrors: form.errors,
        processing: form.processing,
        openDialogForOrder,
        handleSubmitApprove,
    };
};

