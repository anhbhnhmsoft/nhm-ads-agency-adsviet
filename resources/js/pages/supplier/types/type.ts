import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';
import { MonthlySpendingFeeItem } from '@/pages/service-package/types/type';

export type SupplierItem = {
    id: string;
    name: string;
    open_fee: string;
    supplier_fee_percent?: string;
    monthly_spending_fee_structure?: MonthlySpendingFeeItem[];
    disabled: boolean;
    created_at?: string;
    updated_at?: string;
};

export type CreateSupplierForm = {
    name: string;
    open_fee: string;
    supplier_fee_percent?: string;
    monthly_spending_fee_structure: MonthlySpendingFeeItem[];
    disabled: boolean;
};

export type SupplierListQuery = BaseSearchRequest<{
    keyword?: string;
}>;

export type SupplierPagination = LaravelPaginator<SupplierItem>;

