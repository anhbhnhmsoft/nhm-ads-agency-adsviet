import useNestedState from '@/hooks/use-nested-state';
import { router } from '@inertiajs/react';
import { service_packages_index } from '@/routes';
import { ServicePackageListQuery } from '@/pages/service-package/types/type';

export const useSearchServicePackage = () => {
    const [query, setQuery] = useNestedState<ServicePackageListQuery['filter']>({
        keyword: ""
    });

    const handleSearch = () => {
        router.get(service_packages_index(), {
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
