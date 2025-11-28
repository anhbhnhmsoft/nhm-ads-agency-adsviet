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

const parseNumberOrNull = (value?: number | string | null) => {
    if (value === undefined || value === null || value === '' || value === 'null') {
        return null;
    }
    const parsed = Number(value);
    return Number.isNaN(parsed) ? null : parsed;
};

export const useSearchCustomerList = (initial?: CustomerListQuery['filter']) => {
    const [query, setQuery] = useNestedState<CustomerListQuery['filter']>(defaultCustomerFilter);
    const serializedInitial = useMemo(() => JSON.stringify(initial ?? {}), [initial]);

    useEffect(() => {
        if (initial && Object.keys(initial).length > 0) {
            setQuery({
                keyword: initial.keyword ?? '',
                manager_id: parseNumberOrNull(initial.manager_id),
                employee_id: parseNumberOrNull(initial.employee_id),
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
        if (typeof query.manager_id === 'number' && query.manager_id > 0) {
            payload.manager_id = query.manager_id;
        }
        if (typeof query.employee_id === 'number' && query.employee_id > 0) {
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
