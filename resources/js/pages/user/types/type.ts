import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';
import { _UserRole } from '@/lib/types/constants';

export type EmployeeListItem = {
    id: number;
    username: string;
    phone: string | null;
    disabled: boolean;
    referral_code: string;
    role: _UserRole;
}

export type EmployeeListPagination = LaravelPaginator<EmployeeListItem>;


export type EmployeeListQuery = BaseSearchRequest<{
    keyword?: string;
}>

export type CustomerListQuery = BaseSearchRequest<{
    keyword?: string;
}>

export type CustomerListItem = {
    id: number;
    name: string;
    username: string;
    phone: string | null;
    disabled: boolean;
    role: _UserRole;
    using_telegram: boolean;
    using_whatsapp: boolean;
    referral_code: string;
}
export type CustomerListPagination = LaravelPaginator<CustomerListItem>;
