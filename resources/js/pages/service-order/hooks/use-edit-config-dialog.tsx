import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import { service_orders_update_config } from '@/routes';
import { _PlatformType } from '@/lib/types/constants';

export const useServiceOrderEditConfigDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const [metaEmail, setMetaEmail] = useState('');
    const [displayName, setDisplayName] = useState('');
    const [bmId, setBmId] = useState('');
    const [infoFanpage, setInfoFanpage] = useState('');
    const [infoWebsite, setInfoWebsite] = useState('');
    const [paymentType, setPaymentType] = useState('');
    const [assetAccess, setAssetAccess] = useState<'full_asset' | 'basic_asset'>('full_asset');
    const [timezoneBm, setTimezoneBm] = useState('');

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        const config = order.config_account || {};
        const isGoogle = order.package?.platform === _PlatformType.GOOGLE;
        setSelectedOrder(order);
        setMetaEmail((config.meta_email as string) || '');
        setDisplayName((config.display_name as string) || '');
        setBmId((config.bm_id as string) || '');
        setInfoFanpage(isGoogle ? '' : (config.info_fanpage as string) || '');
        setInfoWebsite(isGoogle ? '' : (config.info_website as string) || '');
        setPaymentType((config.payment_type as string) || '');
        setAssetAccess(((config.asset_access as 'full_asset' | 'basic_asset') || 'full_asset'));
        setTimezoneBm((config.timezone_bm as string) || '');
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
                info_fanpage: infoFanpage || undefined,
                info_website: infoWebsite || undefined,
                payment_type: paymentType || undefined,
                asset_access: assetAccess || undefined,
                timezone_bm: timezoneBm || undefined,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    setSelectedOrder(null);
                },
            },
        );
    }, [bmId, displayName, metaEmail, infoFanpage, infoWebsite, paymentType, assetAccess, timezoneBm, selectedOrder]);

    return {
        dialogOpen,
        setDialogOpen,
        selectedOrder,
        metaEmail,
        setMetaEmail,
        displayName,
        setDisplayName,
        bmId,
        setBmId,
        infoFanpage,
        setInfoFanpage,
        infoWebsite,
        setInfoWebsite,
        paymentType,
        setPaymentType,
        assetAccess,
        setAssetAccess,
        timezoneBm,
        setTimezoneBm,
        openDialogForOrder,
        handleSubmitUpdate,
    };
};

