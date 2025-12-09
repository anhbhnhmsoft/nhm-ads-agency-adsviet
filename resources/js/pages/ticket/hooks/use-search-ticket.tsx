import useNestedState from '@/hooks/use-nested-state';
import { router } from '@inertiajs/react';
import { ticket_index } from '@/routes';

export type TicketListQuery = {
    filter?: {
        keyword?: string;
        status?: string | number | null;
        status_not_in?: (string | number)[];
    };
};

export const useSearchTicketList = () => {
    const [query, setQuery] = useNestedState<TicketListQuery['filter']>({
        keyword: '',
    });

    const handleSearch = () => {
        const filterPayload: Record<string, any> = {};
        
        if (query.keyword) {
            filterPayload.keyword = query.keyword;
        }
        
        if (query.status !== undefined && query.status !== null) {
            filterPayload.status = query.status;
        }
        
        if (query.status_not_in && query.status_not_in.length > 0) {
            filterPayload.status_not_in = query.status_not_in;
        }

        router.get(
            ticket_index(),
            {
                filter: filterPayload,
            },
            {
                replace: true,
                preserveState: true,
                only: ['tickets'],
            }
        );
    };

    return {
        query,
        setQuery,
        handleSearch,
    };
};

