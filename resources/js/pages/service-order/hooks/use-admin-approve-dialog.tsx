import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import { service_orders_approve } from '@/routes';

export const useServiceOrderAdminDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const [metaEmail, setMetaEmail] = useState('');
    const [displayName, setDisplayName] = useState('');
    const [bmId, setBmId] = useState('');

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        const config = order.config_account || {};
        setSelectedOrder(order);
        setMetaEmail((config.meta_email as string) || '');
        setDisplayName((config.display_name as string) || '');
        setBmId((config.bm_id as string) || '');
        setDialogOpen(true);
    }, []);

    const handleSubmitApprove = useCallback(() => {
        if (!selectedOrder) return;
        if (!metaEmail || !displayName || !bmId) {
            return;
        }

        router.post(
            service_orders_approve({ id: selectedOrder.id }).url,
            {
                meta_email: metaEmail,
                display_name: displayName,
                bm_id: bmId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    setSelectedOrder(null);
                },
            },
        );
    }, [bmId, displayName, metaEmail, selectedOrder]);

    return {
        dialogOpen,
        setDialogOpen,
        metaEmail,
        setMetaEmail,
        displayName,
        setDisplayName,
        bmId,
        setBmId,
        openDialogForOrder,
        handleSubmitApprove,
    };
};


