import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';
import { _UserRole } from '@/lib/types/constants';

export type EmployeeListItem = {
    id: number;
    name: string;
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
    manager_id?: number | null;
    employee_id?: number | null;
}>

export type UserOption = {
    id: number;
    name: string;
    username: string;
};

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
    wallet_balance?: number;
    wallet_status?: number;
    owner?: {
        id?: number;
        username?: string;
        role?: _UserRole;
    } | null;
    manager?: {
        id?: number;
        username?: string;
    } | null;
}
export type CustomerListPagination = LaravelPaginator<CustomerListItem>;

export type Employee = {
    id: number;
    name: string;
    username: string;
    phone: string | null;
    role: number;
    disabled: boolean;
}

export type EmployeeFormData = {
    id?: number;
    name: string;
    username: string;
    password?: string;
    phone?: string | null;
    role: number;
    disabled: boolean;
}

export type Manager = {
    id: number;
    name: string;
    username: string;
}

export type EmployeeForAssignment = {
    id: number;
    name: string;
    username: string;
    assigned: boolean;
}

export interface WalletData {
    id: number;
    user_id: number;
    balance: number;
    status: number;
}