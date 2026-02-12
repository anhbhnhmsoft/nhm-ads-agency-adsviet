import useNestedState from '@/hooks/use-nested-state';
import { router } from '@inertiajs/react';
import { commissions_report_index } from '@/routes';
import { CommissionReportQuery } from '@/pages/commission/types/type';

export const useSearchCommissionReport = () => {
    const [query, setQuery] = useNestedState<CommissionReportQuery['filter']>({
        keyword: '',
        employee_id: '',
        customer_id: '',
        type: undefined,
        period: '',
        is_paid: undefined,
        date_from: '',
        date_to: '',
    });

    const handleSearch = () => {
        router.get(
            commissions_report_index(),
            {
                filter: query,
            },
            {
                replace: true,
                preserveState: true,
                only: ['paginator', 'summary_by_employee'],
            }
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
    };
};

