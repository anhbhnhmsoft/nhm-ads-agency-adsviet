import { _PlatformType } from '@/lib/types/constants';
import type { ChildBusinessManager } from '@/pages/business-manager/types/type';
import type {
    AccountConfig,
    ServiceOrder,
} from '@/pages/service-order/types/type';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import {
    business_managers_get_accounts,
    business_managers_get_child_bms,
    service_orders_update_config,
} from '@/routes';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';
import type { BmAccount, BmListItem, AssignMode } from './use-admin-approve-dialog';

export const useServiceOrderEditConfigDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(
        null,
    );
    const [accounts, setAccounts] = useState<AccountFormData[]>([]);
    const [useAccountsStructure, setUseAccountsStructure] = useState(false);
    const [metaEmail, setMetaEmail] = useState('');
    const [displayName, setDisplayName] = useState('');
    const [bmId, setBmId] = useState('');
    const [infoFanpage, setInfoFanpage] = useState('');
    const [infoWebsite, setInfoWebsite] = useState('');
    const [paymentType, setPaymentType] = useState('');
    const [assetAccess, setAssetAccess] = useState<
        'full_asset' | 'basic_asset'
    >('full_asset');
    const [timezoneBm, setTimezoneBm] = useState('');

    // Additional states matching Approve dialog
    const [assignMode, setAssignMode] = useState<AssignMode>('bm');
    const [childBusinessManagers, setChildBusinessManagers] = useState<
        ChildBusinessManager[]
    >([]);
    const [selectedChildBmId, setSelectedChildBmId] = useState<string>('none');
    const [loadingChildBMs, setLoadingChildBMs] = useState(false);
    const [bmList, setBmList] = useState<BmListItem[]>([]);
    const [loadingBmList, setLoadingBmList] = useState(false);
    const [bmAccounts, setBmAccounts] = useState<BmAccount[]>([]);
    const [loadingBmAccounts, setLoadingBmAccounts] = useState(false);
    const [accountIdInput, setAccountIdInput] = useState('');

    // Fetch methods matching Approve dialog
    const fetchBmList = useCallback(async (platform?: number) => {
        setLoadingBmList(true);
        try {
            const params: Record<string, string | number> = {
                per_page: 200,
            };
            if (platform) params['filter[platform]'] = platform;
            const response = await axios.get('/business-managers/list', { params });
            const data = response.data;
            if (data?.data && Array.isArray(data.data)) {
                setBmList(data.data);
            } else {
                setBmList([]);
            }
        } catch (error) {
            console.error('Error fetching BM list:', error);
            setBmList([]);
        } finally {
            setLoadingBmList(false);
        }
    }, []);

    const fetchChildBusinessManagers = useCallback(
        async (parentBmId: string) => {
            if (!parentBmId || parentBmId.trim() === '') {
                setChildBusinessManagers([]);
                setSelectedChildBmId('none');
                return;
            }

            setLoadingChildBMs(true);
            try {
                const response = await axios.get(
                    business_managers_get_child_bms({ parentBmId }).url,
                );
                if (
                    response.data.success &&
                    Array.isArray(response.data.data)
                ) {
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
        },
        [],
    );

    const fetchBmAccounts = useCallback(
        async (bmIdValue: string, platform?: number) => {
            if (!bmIdValue || bmIdValue.trim() === '') {
                setBmAccounts([]);
                return;
            }

            setLoadingBmAccounts(true);
            try {
                const params: Record<string, string | number> = {};
                if (platform) params.platform = platform;
                const response = await axios.get(
                    business_managers_get_accounts({ bmId: bmIdValue }).url,
                    { params },
                );
                if (
                    response.data.success &&
                    Array.isArray(response.data.data)
                ) {
                    setBmAccounts(response.data.data);
                } else {
                    setBmAccounts([]);
                }
            } catch (error) {
                console.error('Error fetching BM accounts:', error);
                setBmAccounts([]);
            } finally {
                setLoadingBmAccounts(false);
            }
        },
        [],
    );

    const handleSelectBmFromList = useCallback(
        (bmIdVal: string) => {
            setBmId(bmIdVal);
            if (selectedOrder) {
                const isGoogle = selectedOrder.package?.platform === _PlatformType.GOOGLE;
                if (!isGoogle) {
                    fetchChildBusinessManagers(bmIdVal);
                    fetchBmAccounts(bmIdVal, selectedOrder.package?.platform ?? undefined);
                }
            }
        },
        [selectedOrder, fetchChildBusinessManagers, fetchBmAccounts],
    );

    const handleSelectAccountFromList = useCallback((accId: string) => {
        setAccountIdInput(accId);
    }, []);

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
        
        setAssignMode('bm');
        setChildBusinessManagers([]);
        setSelectedChildBmId('none');
        setBmAccounts([]);
        setAccountIdInput('');
    }, []);

    const cleanAccountData = useCallback(
        (account: AccountConfig): AccountFormData => {
            return {
                ...account,
                bm_ids:
                    account.bm_ids?.filter((id: string) => id?.trim()) || [],
                fanpages:
                    account.fanpages?.filter((fp: string) => fp?.trim()) || [],
                websites:
                    account.websites?.filter((ws: string) => ws?.trim()) || [],
            };
        },
        [],
    );

    const openDialogForOrder = useCallback(
        (order: ServiceOrder) => {
            resetFormState();

            const config = order.config_account || {};
            const isGoogle = order.package?.platform === _PlatformType.GOOGLE;
            const packagePaymentType =
                order.package?.payment_type === 'postpay'
                    ? 'postpay'
                    : 'prepay';
            setSelectedOrder(order);

            const configAccounts = config.accounts;
            if (Array.isArray(configAccounts) && configAccounts.length > 0) {
                setUseAccountsStructure(true);
                setAccounts(configAccounts.map(cleanAccountData));
                setPaymentType(packagePaymentType);
            } else {
                setUseAccountsStructure(false);
                setMetaEmail((config.meta_email as string) || '');
                setDisplayName((config.display_name as string) || '');
                
                const bmIdVal = (config.bm_id as string) || '';
                setBmId(bmIdVal);
                
                setInfoFanpage(
                    isGoogle ? '' : (config.info_fanpage as string) || '',
                );
                setInfoWebsite(
                    isGoogle ? '' : (config.info_website as string) || '',
                );
                setPaymentType(packagePaymentType);
                setAssetAccess(
                    (config.asset_access as 'full_asset' | 'basic_asset') ||
                        'full_asset',
                );
                setTimezoneBm((config.timezone_bm as string) || '');

                // Restore assign fields
                const assignModeVal = (config.assign_mode as AssignMode) || 'bm';
                setAssignMode(assignModeVal);
                
                const childBmIdVal = (config.child_bm_id as string) || 'none';
                setSelectedChildBmId(childBmIdVal);

                const accountIdVal = (config.account_id as string) || '';
                setAccountIdInput(accountIdVal);

                if (bmIdVal && !isGoogle) {
                    fetchChildBusinessManagers(bmIdVal);
                    fetchBmAccounts(bmIdVal, order.package?.platform ?? undefined);
                }
            }
            fetchBmList(order.package?.platform ?? undefined);
            setDialogOpen(true);
        },
        [resetFormState, cleanAccountData, fetchBmList, fetchChildBusinessManagers, fetchBmAccounts],
    );

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
            assign_mode?: AssignMode;
            child_bm_id?: string | null;
            account_id?: string | null;
        };

        const payload: UpdateConfigPayload = {
            payment_type:
                selectedOrder.package?.payment_type === 'postpay'
                    ? 'postpay'
                    : 'prepay',
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
            payload.assign_mode = assignMode;
            payload.child_bm_id =
                assignMode === 'bm' && selectedChildBmId !== 'none' && selectedChildBmId
                    ? selectedChildBmId
                    : null;
            payload.account_id =
                assignMode === 'account' && accountIdInput
                    ? accountIdInput
                    : null;
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
    }, [
        bmId,
        displayName,
        metaEmail,
        infoFanpage,
        infoWebsite,
        paymentType,
        assetAccess,
        timezoneBm,
        selectedOrder,
        useAccountsStructure,
        accounts,
        cleanAccountData,
        resetFormState,
        assignMode,
        selectedChildBmId,
        accountIdInput,
    ]);

    const handleDialogOpenChange = useCallback(
        (open: boolean) => {
            setDialogOpen(open);
            if (!open) {
                setSelectedOrder(null);
                resetFormState();
            }
        },
        [resetFormState],
    );

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
        
        // Added properties
        assignMode,
        setAssignMode,
        childBusinessManagers,
        selectedChildBmId,
        setSelectedChildBmId,
        loadingChildBMs,
        bmList,
        loadingBmList,
        bmAccounts,
        loadingBmAccounts,
        accountIdInput,
        setAccountIdInput,
        handleSelectBmFromList,
        handleSelectAccountFromList,
    };
};
