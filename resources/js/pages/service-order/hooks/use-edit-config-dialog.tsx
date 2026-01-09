import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import type { ServiceOrder, AccountConfig } from '@/pages/service-order/types/type';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { service_orders_update_config } from '@/routes';
import { _PlatformType } from '@/lib/types/constants';

export const useServiceOrderEditConfigDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const [accounts, setAccounts] = useState<AccountFormData[]>([]);
    const [useAccountsStructure, setUseAccountsStructure] = useState(false);
    const [metaEmail, setMetaEmail] = useState('');
    const [displayName, setDisplayName] = useState('');
    const [bmId, setBmId] = useState('');
    const [infoFanpage, setInfoFanpage] = useState('');
    const [infoWebsite, setInfoWebsite] = useState('');
    const [paymentType, setPaymentType] = useState('');
    const [assetAccess, setAssetAccess] = useState<'full_asset' | 'basic_asset'>('full_asset');
    const [timezoneBm, setTimezoneBm] = useState('');

    const resetFormState = useCallback(() => {
        setAccounts([]);
        setUseAccountsStructure(false);
        setMetaEmail('');
        setDisplayName('');
        setBmId('');
        setInfoFanpage('');
        setInfoWebsite('');
        setPaymentType('');
        setAssetAccess('full_asset');
        setTimezoneBm('');
    }, []);

    const cleanAccountData = useCallback((account: AccountConfig): AccountFormData => {
        return {
            ...account,
            bm_ids: account.bm_ids?.filter((id: string) => id?.trim()) || [],
            fanpages: account.fanpages?.filter((fp: string) => fp?.trim()) || [],
            websites: account.websites?.filter((ws: string) => ws?.trim()) || [],
        };
    }, []);

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        resetFormState();
        
        const config = order.config_account || {};
        const isGoogle = order.package?.platform === _PlatformType.GOOGLE;
        setSelectedOrder(order);

        const configAccounts = config.accounts;
        if (Array.isArray(configAccounts) && configAccounts.length > 0) {
            setUseAccountsStructure(true);
            setAccounts(configAccounts.map(cleanAccountData));
            setPaymentType((config.payment_type as string) || '');
        } else {
            setUseAccountsStructure(false);
            setMetaEmail((config.meta_email as string) || '');
            setDisplayName((config.display_name as string) || '');
            setBmId((config.bm_id as string) || '');
            setInfoFanpage(isGoogle ? '' : (config.info_fanpage as string) || '');
            setInfoWebsite(isGoogle ? '' : (config.info_website as string) || '');
            setPaymentType((config.payment_type as string) || '');
            setAssetAccess(((config.asset_access as 'full_asset' | 'basic_asset') || 'full_asset'));
            setTimezoneBm((config.timezone_bm as string) || '');
        }
        setDialogOpen(true);
    }, [resetFormState, cleanAccountData]);

    const handleSubmitUpdate = useCallback(() => {
        if (!selectedOrder) return;

        type UpdateConfigPayload = {
            payment_type?: string;
            accounts?: AccountFormData[];
            meta_email?: string;
            display_name?: string;
            bm_id?: string;
            info_fanpage?: string;
            info_website?: string;
            asset_access?: 'full_asset' | 'basic_asset';
            timezone_bm?: string;
        };

        const payload: UpdateConfigPayload = {
            payment_type: paymentType || undefined,
        };

        if (useAccountsStructure && accounts.length > 0) {
            payload.accounts = accounts.map(cleanAccountData);
        } else {
            payload.meta_email = metaEmail || undefined;
            payload.display_name = displayName || undefined;
            payload.bm_id = bmId || undefined;
            payload.info_fanpage = infoFanpage || undefined;
            payload.info_website = infoWebsite || undefined;
            payload.asset_access = assetAccess || undefined;
            payload.timezone_bm = timezoneBm || undefined;
        }

        router.put(
            service_orders_update_config({ id: selectedOrder.id }).url,
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    setSelectedOrder(null);
                    resetFormState();
                },
                onError: (errors) => {
                    console.error('Update config error:', errors);
                },
            },
        );
    }, [bmId, displayName, metaEmail, infoFanpage, infoWebsite, paymentType, assetAccess, timezoneBm, selectedOrder, useAccountsStructure, accounts, cleanAccountData, resetFormState]);

    const handleDialogOpenChange = useCallback((open: boolean) => {
        setDialogOpen(open);
        if (!open) {
            setSelectedOrder(null);
            resetFormState();
        }
    }, [resetFormState]);

    return {
        dialogOpen,
        setDialogOpen: handleDialogOpenChange,
        selectedOrder,
        useAccountsStructure,
        accounts,
        setAccounts,
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

