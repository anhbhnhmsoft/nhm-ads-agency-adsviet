import { _PlatformType } from '@/lib/types/constants';
import type { ChildBusinessManager } from '@/pages/business-manager/types/type';
import type {
    AccountConfig,
    ServiceOrder,
    ServiceOrderConfigAccount,
} from '@/pages/service-order/types/type';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import {
    business_managers_get_accounts,
    business_managers_get_child_bms,
    service_orders_approve,
} from '@/routes';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

export type BmAccount = {
    id: string;
    account_id: string;
    account_name: string;
    account_status: number | null;
    currency: string;
    service_user_id: string | null;
    owner_name: string | null;
};

export type BmListItem = {
    id: string;
    bm_ids: string[];
    bm_name: string;
    name: string;
    platform: number;
    owner_name: string | null;
    total_accounts: number;
    total_spend: string;
};

export type AssignMode = 'bm' | 'account';

export const useServiceOrderAdminDialog = () => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<ServiceOrder | null>(
        null,
    );
    const [accounts, setAccounts] = useState<AccountFormData[]>([]);
    const [useAccountsStructure, setUseAccountsStructure] = useState(false);
    const accountsRef = useRef<AccountFormData[]>([]);
    const [childBusinessManagers, setChildBusinessManagers] = useState<
        ChildBusinessManager[]
    >([]);
    const [selectedChildBmId, setSelectedChildBmId] = useState<string>('none');
    const [loadingChildBMs, setLoadingChildBMs] = useState(false);

    // Assign mode: 'bm' = gán BM, 'account' = gán tài khoản cụ thể
    const [assignMode, setAssignMode] = useState<AssignMode>('bm');

    // BM/MCC list for dropdown
    const [bmList, setBmList] = useState<BmListItem[]>([]);
    const [loadingBmList, setLoadingBmList] = useState(false);

    // Accounts in selected BM (for dropdown)
    const [bmAccounts, setBmAccounts] = useState<BmAccount[]>([]);
    const [loadingBmAccounts, setLoadingBmAccounts] = useState(false);

    // ---- Tab "Gán BM" ----
    // bmId = ID BM nhập tay hoặc chọn từ dropdown

    // ---- Tab "Gán tài khoản" ----
    // accountIdInput = ID tài khoản nhập tay (như act_xxx)

    const form = useForm({
        meta_email: '',
        display_name: '',
        bm_id: '',
        account_id_input: '',
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

    // Fetch BM/MCC list for dropdown
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

    // Fetch child business managers when BM ID changes
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

    // Fetch accounts in a BM/MCC
    const fetchBmAccounts = useCallback(
        async (bmId: string, platform?: number) => {
            if (!bmId || bmId.trim() === '') {
                setBmAccounts([]);
                return;
            }

            setLoadingBmAccounts(true);
            try {
                const params: Record<string, string | number> = {};
                if (platform) params.platform = platform;
                const response = await axios.get(
                    business_managers_get_accounts({ bmId }).url,
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

    const openDialogForOrder = useCallback(
        (order: ServiceOrder) => {
            form.reset();
            form.clearErrors();

            setAccounts([]);
            setUseAccountsStructure(false);
            accountsRef.current = [];
            setChildBusinessManagers([]);
            setSelectedChildBmId('none');
            setBmAccounts([]);
            setAssignMode('bm');

            const config = order.config_account || {};
            const typedConfig: ServiceOrderConfigAccount = config;

            setSelectedOrder(order);
            const isGoogle = order.package?.platform === _PlatformType.GOOGLE;
            const packagePaymentType =
                order.package?.payment_type === 'postpay'
                    ? 'postpay'
                    : 'prepay';
            const configAccounts = config.accounts;

            if (Array.isArray(configAccounts) && configAccounts.length > 0) {
                setUseAccountsStructure(true);
                const cleanedAccounts = configAccounts.map(
                    (acc: AccountConfig) => ({
                        ...acc,
                        bm_ids:
                            acc.bm_ids?.filter((id: string) => id?.trim()) ||
                            [],
                        fanpages:
                            acc.fanpages?.filter((fp: string) => fp?.trim()) ||
                            [],
                        websites:
                            acc.websites?.filter((ws: string) => ws?.trim()) ||
                            [],
                    }),
                );
                setAccounts(cleanedAccounts);
                accountsRef.current = cleanedAccounts;
                form.setData({
                    meta_email: '',
                    display_name: '',
                    bm_id: '',
                    account_id_input: '',
                    info_fanpage: '',
                    info_website: '',
                    payment_type: packagePaymentType,
                    asset_access: 'full_asset',
                    timezone_bm: '',
                    accounts: cleanedAccounts,
                });
            } else {
                setUseAccountsStructure(false);
                setAccounts([]);
                accountsRef.current = [];
                const bmIdValue = typedConfig.bm_id || '';
                const childBmIdValue = typedConfig.child_bm_id || '';

                form.setData({
                    meta_email: typedConfig.meta_email || '',
                    display_name: typedConfig.display_name || '',
                    bm_id: bmIdValue,
                    account_id_input: '',
                    info_fanpage: isGoogle
                        ? ''
                        : typedConfig.info_fanpage || '',
                    info_website: isGoogle
                        ? ''
                        : typedConfig.info_website || '',
                    payment_type: packagePaymentType,
                    asset_access: typedConfig.asset_access || 'full_asset',
                    timezone_bm: typedConfig.timezone_bm || '',
                    accounts: [],
                });

                if (childBmIdValue) {
                    setSelectedChildBmId(childBmIdValue);
                } else {
                    setSelectedChildBmId('none');
                }

                if (bmIdValue && !isGoogle) {
                    fetchChildBusinessManagers(bmIdValue);
                    fetchBmAccounts(bmIdValue, order.package?.platform);
                }
            }

            fetchBmList(order.package?.platform ?? undefined);
            setDialogOpen(true);
        },
        [form, fetchChildBusinessManagers, fetchBmAccounts, fetchBmList],
    );

    // Determine account_id to send to backend
    const getAccountIdToSubmit = useCallback((overrideAccountId?: string | null): string | null => {
        if (assignMode === 'account') {
            // Ưu tiên override từ component cha (currentAccountId từ accountIdList)
            const fromOverride = overrideAccountId?.trim();
            if (fromOverride) return fromOverride;
            // Fallback: từ form data
            return form.data.account_id_input?.trim() || null;
        }
        return null;
    }, [assignMode, form.data.account_id_input]);

    const handleSubmitApprove = useCallback((overrideAccountId?: string | null) => {
        if (!selectedOrder) return;

        // Validate: tab "Gán tài khoản" phải nhập ID
        if (assignMode === 'account') {
            const accountIdFromOverride = overrideAccountId?.trim();
            const accountIdFromForm = form.data.account_id_input?.trim();
            if (!accountIdFromOverride && !accountIdFromForm) {
                form.setError('account_id', 'Vui lòng nhập ID tài khoản khi chọn tab Gán tài khoản');
                return;
            }
        }

        const currentAccounts = accountsRef.current;

        let accountsToSubmit: AccountFormData[] = [];

        if (useAccountsStructure && currentAccounts.length > 0) {
            accountsToSubmit = currentAccounts.map((acc) => ({
                ...acc,
                bm_ids: (acc.bm_ids || []).filter(
                    (id: string) => id != null && String(id).trim() !== '',
                ),
                fanpages: (acc.fanpages || []).filter(
                    (fp: string) => fp && String(fp).trim() !== '',
                ),
                websites: (acc.websites || []).filter(
                    (ws: string) => ws && String(ws).trim() !== '',
                ),
            }));
        }

        // Xác định account_id gửi lên backend
        const accountId = getAccountIdToSubmit(overrideAccountId);

        form.transform(() => ({
            ...form.data,
            bm_id: form.data.bm_id || '',
            child_bm_id:
                assignMode === 'bm' && selectedChildBmId !== 'none' && selectedChildBmId
                    ? selectedChildBmId
                    : null,
            account_id: accountId,
            assign_mode: assignMode,
            accounts: accountsToSubmit,
            payment_type:
                selectedOrder.package?.payment_type === 'postpay'
                    ? 'postpay'
                    : 'prepay',
        }));

        form.post(service_orders_approve({ id: selectedOrder.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                setSelectedOrder(null);
                setAccounts([]);
                accountsRef.current = [];
                setUseAccountsStructure(false);
                setChildBusinessManagers([]);
                setSelectedChildBmId('none');
                setAssignMode('bm');
                form.reset();
                form.clearErrors();
            },
            onError: (errors: any) => {
                console.error('Approve error:', errors);
            },
        });
    }, [
        form,
        selectedOrder,
        useAccountsStructure,
        selectedChildBmId,
        assignMode,
        getAccountIdToSubmit,
    ]);

    const handleDialogOpenChange = useCallback(
        (open: boolean) => {
            setDialogOpen(open);
            if (!open) {
                setSelectedOrder(null);
                setAccounts([]);
                setUseAccountsStructure(false);
                setChildBusinessManagers([]);
                setSelectedChildBmId('none');
                setAssignMode('bm');
                setBmAccounts([]);
                form.reset();
                form.clearErrors();
            }
        },
        [form],
    );

    // Tab Gán BM: handle BM ID change
    const handleBmIdChange = useCallback(
        (value: string) => {
            form.setData('bm_id', value);
            setSelectedChildBmId('none');
            setBmAccounts([]);

            if (selectedOrder?.package?.platform === _PlatformType.META) {
                fetchChildBusinessManagers(value);
            }
            fetchBmAccounts(value, selectedOrder?.package?.platform);
        },
        [form, selectedOrder, fetchChildBusinessManagers, fetchBmAccounts],
    );

    // Dropdown "Chọn BM có sẵn" → gán bmId
    const handleSelectBmFromList = useCallback(
        (bmId: string) => {
            handleBmIdChange(bmId);
        },
        [handleBmIdChange],
    );

    // Dropdown "Chọn tài khoản" → gán accountId
    const handleSelectAccountFromList = useCallback(
        (accountId: string) => {
            form.setData('account_id_input', accountId);
        },
        [form],
    );

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
        // Tab Gán BM
        bmId: form.data.bm_id,
        setBmId: handleBmIdChange,
        // Tab Gán tài khoản
        accountIdInput: form.data.account_id_input,
        setAccountIdInput: (value: string) => form.setData('account_id_input', value),
        // Info
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
        // Assign mode
        assignMode,
        setAssignMode,
        // BM list + child
        childBusinessManagers,
        selectedChildBmId,
        setSelectedChildBmId,
        loadingChildBMs,
        bmList,
        loadingBmList,
        handleSelectBmFromList,
        // BM accounts + selected account
        bmAccounts,
        loadingBmAccounts,
        handleSelectAccountFromList,
        formErrors: form.errors,
        processing: form.processing,
        openDialogForOrder,
        handleSubmitApprove,
    };
};
