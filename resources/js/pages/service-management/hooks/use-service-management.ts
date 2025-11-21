import { useState } from 'react';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import type { MetaAccount, MetaCampaign, ServiceManagementHook } from '@/pages/service-management/types/types';

const extractData = (payload: any) => {
    if (!payload) return [];
    if (Array.isArray(payload.data)) {
        return payload.data;
    }
    if (payload.data?.data) {
        return payload.data.data;
    }
    return [];
};

export const useServiceManagement = (services: ServiceOrder[]): ServiceManagementHook => {
    const { t } = useTranslation();
    const [selectedService, setSelectedService] = useState<ServiceOrder | null>(null);
    const [accounts, setAccounts] = useState<MetaAccount[]>([]);
    const [accountsLoading, setAccountsLoading] = useState(false);
    const [accountsError, setAccountsError] = useState<string | null>(null);

    const [campaignsByAccount, setCampaignsByAccount] = useState<Record<string, MetaCampaign[]>>({});
    const [campaignLoadingId, setCampaignLoadingId] = useState<string | null>(null);
    const [campaignError, setCampaignError] = useState<string | null>(null);
    const [selectedAccountId, setSelectedAccountId] = useState<string | null>(null);

    const closeDialog = () => {
        setSelectedService(null);
        setAccounts([]);
        setCampaignsByAccount({});
        setSelectedAccountId(null);
        setAccountsError(null);
        setCampaignError(null);
    };

    const loadAccounts = async (serviceId: string) => {
        setAccountsLoading(true);
        setAccountsError(null);
        try {
            const response = await axios.get(`/meta/${serviceId}/accounts`, { params: { per_page: 20 } });
            const items = extractData(response.data?.data);
            setAccounts(items as MetaAccount[]);
        } catch (error: any) {
            setAccountsError(error?.response?.data?.message || t('service_management.accounts_error'));
        } finally {
            setAccountsLoading(false);
        }
    };

    const handleViewService = async (service: ServiceOrder) => {
        setSelectedService(service);
        setAccounts([]);
        setCampaignsByAccount({});
        setSelectedAccountId(null);
        await loadAccounts(service.id);
    };

    const handleLoadCampaigns = async (account: MetaAccount) => {
        if (!selectedService) return;
        setSelectedAccountId(account.id);
        setCampaignError(null);
        setCampaignLoadingId(account.id);
        try {
            const response = await axios.get(`/meta/${selectedService.id}/${account.id}/campaigns`, {
                params: { per_page: 25 },
            });
            const items = extractData(response.data?.data);
            setCampaignsByAccount((prev) => ({
                ...prev,
                [account.id]: items as MetaCampaign[],
            }));
        } catch (error: any) {
            setCampaignError(error?.response?.data?.message || t('service_management.campaigns_error'));
        } finally {
            setCampaignLoadingId(null);
        }
    };

    return {
        services,
        selectedService,
        accounts,
        accountsLoading,
        accountsError,
        campaignsByAccount,
        campaignLoadingId,
        campaignError,
        selectedAccountId,
        handleViewService,
        handleLoadCampaigns,
        closeDialog,
    };
};

