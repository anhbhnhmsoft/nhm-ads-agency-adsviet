import useNestedState from '@/hooks/use-nested-state';
import { BusinessManagerListQuery } from '@/pages/business-manager/types/type';
import { business_managers_index } from '@/routes';
import { router } from '@inertiajs/react';

export const useSearchBusinessManager = () => {
    const [query, setQuery] = useNestedState<
        BusinessManagerListQuery['filter']
    >({
        keyword: '',
        platform: undefined,
        start_date: undefined,
        end_date: undefined,
        child_manager_id: undefined,
    });

    const handleSearch = () => {
        router.get(
            business_managers_index().url,
            {
                filter: query,
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'stats', 'childManagers'],
            },
        );
    };

    const handleReset = () => {
        const emptyQuery: BusinessManagerListQuery['filter'] = {
            keyword: '',
            platform: undefined,
            start_date: undefined,
            end_date: undefined,
            child_manager_id: undefined,
        };

        setQuery(emptyQuery);
        router.get(
            business_managers_index().url,
            {},
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'stats', 'childManagers'],
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
