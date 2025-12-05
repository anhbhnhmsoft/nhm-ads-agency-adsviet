import useNestedState from '@/hooks/use-nested-state';
import { router } from '@inertiajs/react';
import { BusinessManagerListQuery } from '@/pages/business-manager/types/type';

export const useSearchBusinessManager = () => {
    const [query, setQuery] = useNestedState<BusinessManagerListQuery['filter']>({
        keyword: "",
        platform: undefined,
    });

    const handleSearch = () => {
        router.get('/business-managers', {
            filter: query
        }, {
            replace: true,
            preserveState: true,
            only: ['paginator']
        });
    }

    return {
        query,
        setQuery,
        handleSearch,
    }
}

