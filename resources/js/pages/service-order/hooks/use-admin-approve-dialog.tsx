import { useCallback, useState, useRef, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import type { ServiceOrder, ServiceOrderConfigAccount, AccountConfig } from '@/pages/service-order/types/type';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { service_orders_approve, business_managers_get_child_bms } from '@/routes';
import { _PlatformType } from '@/lib/types/constants';
import axios from 'axios';
import type { ChildBusinessManager } from '@/pages/business-manager/types/type';

export const useServiceOrderAdminDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(null);
    const [accounts, setAccounts] = useState<AccountFormData[]>([]);
    const [useAccountsStructure, setUseAccountsStructure] = useState(false);
    const accountsRef = useRef<AccountFormData[]>([]);
    const [childBusinessManagers, setChildBusinessManagers] = useState<ChildBusinessManager[]>([]);
    const [selectedChildBmId, setSelectedChildBmId] = useState<string>('');
    const [loadingChildBMs, setLoadingChildBMs] = useState(false);
    
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

    // Fetch child business managers when BM ID changes
    const fetchChildBusinessManagers = useCallback(async (parentBmId: string) => {
        if (!parentBmId || parentBmId.trim() === '') {
            setChildBusinessManagers([]);
            setSelectedChildBmId('');
            return;
        }

        setLoadingChildBMs(true);
        try {
            const response = await axios.get(business_managers_get_child_bms({ parentBmId }).url);
            if (response.data.success && Array.isArray(response.data.data)) {
                setChildBusinessManagers(response.data.data);
            } else {
                setChildBusinessManagers([]);
            }
        } catch (error) {
            console.error('Error fetching child business managers:', error);
            setChildBusinessManagers([]);
        } finally {
            setLoadingChildBMs(false);
        }
    }, []);

    const openDialogForOrder = useCallback((order: ServiceOrder) => {
        form.reset();
        form.clearErrors();
        
        setAccounts([]);
        setUseAccountsStructure(false);
        accountsRef.current = [];
        setChildBusinessManagers([]);
        setSelectedChildBmId('');
        
        const config = order.config_account || {};
        setSelectedOrder(order);
        const isGoogle = order.package?.platform === _PlatformType.GOOGLE;        
        const configAccounts = config.accounts;
        
        if (Array.isArray(configAccounts) && configAccounts.length > 0) {
            setUseAccountsStructure(true);
            const cleanedAccounts = configAccounts.map((acc: AccountConfig) => ({
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
            const typedConfig: ServiceOrderConfigAccount = config;
            const bmIdValue = typedConfig.bm_id || '';
            const childBmIdValue = typedConfig.child_bm_id || '';
            
            form.setData({
                meta_email: typedConfig.meta_email || '',
                display_name: typedConfig.display_name || '',
                bm_id: bmIdValue,
                info_fanpage: isGoogle ? '' : typedConfig.info_fanpage || '',
                info_website: isGoogle ? '' : typedConfig.info_website || '',
                payment_type: typedConfig.payment_type || '',
                asset_access: typedConfig.asset_access || 'full_asset',
                timezone_bm: typedConfig.timezone_bm || '',
                accounts: [],
            });
            
            if (childBmIdValue) {
                setSelectedChildBmId(childBmIdValue);
            }
            
            if (bmIdValue && !isGoogle) {
                fetchChildBusinessManagers(bmIdValue);
            }
        }
        setDialogOpen(true);
    }, [form, fetchChildBusinessManagers]);

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
            bm_id: form.data.bm_id,
            child_bm_id: selectedChildBmId || null,
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
                    setChildBusinessManagers([]);
                    setSelectedChildBmId('');
                    form.reset();
                    form.clearErrors();
                },
                onError: (errors: any) => {
                    console.error('Approve error:', errors);
                },
            },
        );
    }, [form, selectedOrder, useAccountsStructure, selectedChildBmId]);

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

    const handleBmIdChange = useCallback((value: string) => {
        form.setData('bm_id', value);
        setSelectedChildBmId('');
        
        if (selectedOrder?.package?.platform === _PlatformType.META) {
            fetchChildBusinessManagers(value);
        }
    }, [form, selectedOrder, fetchChildBusinessManagers]);

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
        setBmId: handleBmIdChange,
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
        childBusinessManagers,
        selectedChildBmId,
        setSelectedChildBmId,
        loadingChildBMs,
        formErrors: form.errors,
        processing: form.processing,
        openDialogForOrder,
        handleSubmitApprove,
    };
};

