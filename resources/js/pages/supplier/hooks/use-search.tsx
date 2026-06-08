import useNestedState from '@/hooks/use-nested-state';
import { SupplierListQuery } from '@/pages/supplier/types/type';
import { suppliers_index } from '@/routes';
import { router } from '@inertiajs/react';

export const useSearchSupplier = () => {
    const [query, setQuery] = useNestedState<SupplierListQuery['filter']>({
        keyword: '',
    });

    const handleSearch = () => {
        router.get(
            suppliers_index().url,
            {
                filter: query,
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator'],
            },
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
    };
};
