import useNestedState from '@/hooks/use-nested-state';
import type { _PlatformType as PlatformTypeEnum } from '@/lib/types/constants';
import { service_management_index } from '@/routes';
import { router, usePage } from '@inertiajs/react';

export type ServiceManagementListFilter = {
    keyword?: string;
    manager_id?: string;
    platform?: PlatformTypeEnum;
    start_date?: string;
    end_date?: string;
    child_manager_id?: string;
    customer_id?: string;
    has_spend?: string;
};

export const useSearchServiceManagement = () => {
    const { url } = usePage();

    const params = new URLSearchParams(url.split('?')[1] || '');
    const initialKeyword = params.get('filter[keyword]') ?? '';
    const initialManagerId = params.get('filter[manager_id]') ?? undefined;
    const initialPlatformRaw = params.get('filter[platform]');
    const initialPlatform = initialPlatformRaw
        ? (Number(initialPlatformRaw) as PlatformTypeEnum)
        : undefined;
    const initialStartDate = params.get('filter[start_date]') ?? undefined;
    const initialEndDate = params.get('filter[end_date]') ?? undefined;
    const initialChildManagerId =
        params.get('filter[child_manager_id]') ?? undefined;
    const initialCustomerId =
        params.get('filter[customer_id]') ?? undefined;
    const initialHasSpend =
        params.get('filter[has_spend]') ?? undefined;

    const [query, setQuery] = useNestedState<ServiceManagementListFilter>({
        keyword: initialKeyword,
        manager_id: initialManagerId,
        platform: initialPlatform,
        start_date: initialStartDate,
        end_date: initialEndDate,
        child_manager_id: initialChildManagerId,
        customer_id: initialCustomerId,
        has_spend: initialHasSpend,
    });

    const handleSearch = () => {
        router.get(
            service_management_index().url,
            {
                filter: query,
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'stats', 'totals'],
            },
        );
    };

    const handleReset = () => {
        const emptyQuery: ServiceManagementListFilter = {
            keyword: '',
            manager_id: undefined,
            platform: undefined,
            start_date: undefined,
            end_date: undefined,
            child_manager_id: undefined,
            customer_id: undefined,
            has_spend: undefined,
        };

        setQuery(emptyQuery);
        router.get(
            service_management_index().url,
            {},
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'stats', 'totals'],
            },
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
        handleReset,
    };
};
