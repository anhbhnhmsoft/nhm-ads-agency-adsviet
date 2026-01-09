import { useCallback, useState, useRef, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { service_orders_approve } from '@/routes';
import { _PlatformType } from '@/lib/types/constants';

export const useServiceOrderAdminDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const [accounts, setAccounts] = useState<AccountFormData[]>([]);
    const [useAccountsStructure, setUseAccountsStructure] = useState(false);
    const accountsRef = useRef<AccountFormData[]>([]);
    
    const form = useForm({
        meta_email: '',
        display_name: '',
        bm_id: '',
        info_fanpage: '',
        info_website: '',
        payment_type: '',
        asset_access: '',
        timezone_bm: '',
        accounts: [] as AccountFormData[],
    });

    useEffect(() => {
        accountsRef.current = accounts;
    }, [accounts]);

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        form.reset();
        form.clearErrors();
        
        setAccounts([]);
        setUseAccountsStructure(false);
        accountsRef.current = [];
        
        const config = order.config_account || {};
        setSelectedOrder(order);
        const isGoogle = order.package?.platform === _PlatformType.GOOGLE;        
        const configAccounts = config.accounts;
        
        if (Array.isArray(configAccounts) && configAccounts.length > 0) {
            setUseAccountsStructure(true);
            const cleanedAccounts = configAccounts.map((acc: any) => ({
                ...acc,
                bm_ids: acc.bm_ids?.filter((id: string) => id?.trim()) || [],
                fanpages: acc.fanpages?.filter((fp: string) => fp?.trim()) || [],
                websites: acc.websites?.filter((ws: string) => ws?.trim()) || [],
            }));
            setAccounts(cleanedAccounts);
            accountsRef.current = cleanedAccounts;
            form.setData({
                meta_email: '',
                display_name: '',
                bm_id: '',
                info_fanpage: '',
                info_website: '',
                payment_type: (config.payment_type as string) || '',
                asset_access: 'full_asset',
                timezone_bm: '',
                accounts: cleanedAccounts,
            });
        } else {
            setUseAccountsStructure(false);
            setAccounts([]);
            accountsRef.current = [];
            form.setData({
                meta_email: (config.meta_email as string) || '',
                display_name: (config.display_name as string) || '',
                bm_id: (config.bm_id as string) || '',
                info_fanpage: isGoogle ? '' : (config.info_fanpage as string) || '',
                info_website: isGoogle ? '' : (config.info_website as string) || '',
                payment_type: (config.payment_type as string) || '',
                asset_access: (config.asset_access as string) || 'full_asset',
                timezone_bm: (config.timezone_bm as string) || '',
                accounts: [],
            });
        }
        setDialogOpen(true);
    }, [form]);

    const handleSubmitApprove = useCallback(() => {
        if (!selectedOrder) return;

        const currentAccounts = accountsRef.current;
        
        let accountsToSubmit: AccountFormData[] = [];
        
        if (useAccountsStructure && currentAccounts.length > 0) {
            accountsToSubmit = currentAccounts.map((acc) => ({
                ...acc,
                bm_ids: (acc.bm_ids || []).filter((id: string) => id != null && String(id).trim() !== ''),
                fanpages: (acc.fanpages || []).filter((fp: string) => fp && String(fp).trim() !== ''),
                websites: (acc.websites || []).filter((ws: string) => ws && String(ws).trim() !== ''),
            }));
        }

        form.transform(() => ({
            ...form.data,
            accounts: accountsToSubmit,
            payment_type: form.data.payment_type || 'prepay',
        }));

        form.post(
            service_orders_approve({ id: selectedOrder.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    setSelectedOrder(null);
                    setAccounts([]);
                    accountsRef.current = [];
                    setUseAccountsStructure(false);
                    form.reset();
                    form.clearErrors();
                },
                onError: (errors: any) => {
                    console.error('Approve error:', errors);
                },
            },
        );
    }, [form, selectedOrder, useAccountsStructure]);

    const handleDialogOpenChange = useCallback((open: boolean) => {
        setDialogOpen(open);
        if (!open) {
            setSelectedOrder(null);
            setAccounts([]);
            setUseAccountsStructure(false);
            form.reset();
            form.clearErrors();
        }
    }, [form]);

    return {
        dialogOpen,
        setDialogOpen: handleDialogOpenChange,
        selectedOrder,
        useAccountsStructure,
        accounts,
        setAccounts,
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
        assetAccess: form.data.asset_access,
        setAssetAccess: (value: string) => form.setData('asset_access', value),
        timezoneBm: form.data.timezone_bm,
        setTimezoneBm: (value: string) => form.setData('timezone_bm', value),
        formErrors: form.errors,
        processing: form.processing,
        openDialogForOrder,
        handleSubmitApprove,
    };
};

