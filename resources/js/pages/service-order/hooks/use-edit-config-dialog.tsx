import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import { service_orders_update_config } from '@/routes';

export const useServiceOrderEditConfigDialog = () => {
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

    const handleSubmitUpdate = useCallback(() => {
        if (!selectedOrder) return;

        router.put(
            service_orders_update_config({ id: selectedOrder.id }).url,
            {
                meta_email: metaEmail || undefined,
                display_name: displayName || undefined,
                bm_id: bmId || undefined,
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
        handleSubmitUpdate,
    };
};

