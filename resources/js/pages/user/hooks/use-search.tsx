import useNestedState from '@/hooks/use-nested-state';
import { EmployeeListQuery } from '@/pages/user/types/type';
import { router } from '@inertiajs/react';
import { user_list_employee } from '@/routes';


export const useSearchEmployeeList = () => {
    const [query, setQuery] = useNestedState<EmployeeListQuery['filter']>({
        keyword: ""
    });

    const handleSearch = () => {
        router.get(user_list_employee(), {
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
