import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';

export type CommissionType = 'service' | 'spending' | 'account';

export type EmployeeCommissionItem = {
    id: string;
    employee_id?: string;
    employee?: {
        id: string;
        name: string;
        username: string;
    };
    service_package_id: string;
    service_package?: {
        id: string;
        name: string;
        platform: number;
    };
    type: CommissionType;
    rate: string;
    min_amount?: string;
    max_amount?: string;
    is_active: boolean;
    description?: string;
};

export type CreateCommissionForm = {
    service_package_id: string;
    type: CommissionType;
    rate: string;
    min_amount?: string;
    max_amount?: string;
    is_active: boolean;
    description?: string;
};

export type CommissionListQuery = BaseSearchRequest<{
    employee_id?: string;
    service_package_id?: string;
    type?: CommissionType;
    is_active?: boolean;
}>;

export type CommissionPagination = LaravelPaginator<EmployeeCommissionItem>;

// Commission Transaction types
export type CommissionTransactionItem = {
    id: string;
    employee_id: string;
    employee?: {
        id: string;
        name: string;
        username: string;
    };
    customer_id?: string;
    customer?: {
        id: string;
        name: string;
        username: string;
    };
    type: CommissionType;
    reference_type?: string;
    reference_id?: string;
    base_amount: string;
    commission_rate: string;
    commission_amount: string;
    period?: string;
    is_paid: boolean;
    paid_at?: string;
    notes?: string;
    created_at?: string;
};

export type CommissionReportQuery = BaseSearchRequest<{
    keyword?: string;
    employee_id?: string;
    customer_id?: string;
    type?: CommissionType;
    period?: string;
    is_paid?: boolean;
    date_from?: string;
    date_to?: string;
}>;

export type CommissionReportPagination = LaravelPaginator<CommissionTransactionItem>;

export type CommissionSummaryItem = {
    employee_id: string;
    employee?: {
        id: string;
        name: string;
        username: string;
    } | null;
    total_base_amount: string;
    total_commission_amount: string;
};

