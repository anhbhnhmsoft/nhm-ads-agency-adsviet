import useNestedState from '@/hooks/use-nested-state';
import { router } from '@inertiajs/react';
import { service_management_index } from '@/routes';
import type { _PlatformType as PlatformTypeEnum } from '@/lib/types/constants';

export type ServiceManagementListFilter = {
    keyword?: string;
    platform?: PlatformTypeEnum;
    start_date?: string;
    end_date?: string;
    child_manager_id?: string;
};

export const useSearchServiceManagement = () => {
    const [query, setQuery] = useNestedState<ServiceManagementListFilter>({
        keyword: '',
        platform: undefined,
        start_date: undefined,
        end_date: undefined,
        child_manager_id: undefined,
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

