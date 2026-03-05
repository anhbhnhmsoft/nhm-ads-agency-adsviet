import useNestedState from '@/hooks/use-nested-state';
import { router, usePage } from '@inertiajs/react';
import { service_management_index } from '@/routes';
import type { _PlatformType as PlatformTypeEnum } from '@/lib/types/constants';

export type ServiceManagementListFilter = {
    keyword?: string;
    manager_id?: string;
    platform?: PlatformTypeEnum;
    start_date?: string;
    end_date?: string;
    child_manager_id?: string;
};

export const useSearchServiceManagement = () => {
    const { url } = usePage();

    const params = new URLSearchParams(url.split('?')[1] || '');
    const initialKeyword = params.get('filter[keyword]') ?? '';
    const initialManagerId = params.get('filter[manager_id]') ?? undefined;
    const initialPlatformRaw = params.get('filter[platform]');
    const initialPlatform = initialPlatformRaw ? (Number(initialPlatformRaw) as PlatformTypeEnum) : undefined;
    const initialStartDate = params.get('filter[start_date]') ?? undefined;
    const initialEndDate = params.get('filter[end_date]') ?? undefined;
    const initialChildManagerId = params.get('filter[child_manager_id]') ?? undefined;

    const [query, setQuery] = useNestedState<ServiceManagementListFilter>({
        keyword: initialKeyword,
        manager_id: initialManagerId,
        platform: initialPlatform,
        start_date: initialStartDate,
        end_date: initialEndDate,
        child_manager_id: initialChildManagerId,
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
                only: ['paginator', 'stats'],
            },
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
    };
};

