import useNestedState from '@/hooks/use-nested-state';
import { EmployeeListQuery, CustomerListQuery } from '@/pages/user/types/type';
import { router } from '@inertiajs/react';
import { user_list, user_list_employee } from '@/routes';
import { useEffect, useMemo } from 'react';

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

const defaultCustomerFilter: CustomerListQuery['filter'] = {
    keyword: '',
    manager_id: null,
    employee_id: null,
};

const parseStringOrNull = (value?: number | string | null) => {
    if (value === undefined || value === null || value === '' || value === 'null') {
        return null;
    }
    return String(value);
};

export const useSearchCustomerList = (initial?: CustomerListQuery['filter']) => {
    const [query, setQuery] = useNestedState<CustomerListQuery['filter']>(defaultCustomerFilter);
    const serializedInitial = useMemo(() => JSON.stringify(initial ?? {}), [initial]);

    useEffect(() => {
        if (initial && Object.keys(initial).length > 0) {
            setQuery({
                keyword: initial.keyword ?? '',
                manager_id: parseStringOrNull(initial.manager_id),
                employee_id: parseStringOrNull(initial.employee_id),
            });
        } else {
            setQuery({ ...defaultCustomerFilter });
        }
    }, [serializedInitial, setQuery]);

    const buildFilterPayload = () => {
        const payload: Record<string, any> = {};
        if (query.keyword) {
            payload.keyword = query.keyword;
        }
        if (query.manager_id && query.manager_id !== 'all') {
            payload.manager_id = query.manager_id;
        }
        if (query.employee_id && query.employee_id !== 'all') {
            payload.employee_id = query.employee_id;
        }
        return payload;
    };

    const handleSearch = () => {
        router.get(
            user_list(),
            {
                filter: buildFilterPayload(),
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'filters'],
            }
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
    };
};
