import type { LaravelPaginator } from '@/lib/types/type';

export type ServiceOrder = {
    id: string;
    status: number;
    status_label?: string | null;
    package: {
        id?: string | null;
        name?: string | null;
        platform?: number | null;
        platform_label?: string | null;
    };
    user?: {
        referrer?: {
            name?: string | null;
        } | null;
    } | null;
    budget: string;
    open_fee?: string;
    top_up_fee?: number;
    config_account?: Record<string, any> | null;
    description?: string | null;
    created_at?: string | null;
};

export type ServiceOrderPagination = LaravelPaginator<ServiceOrder>;


