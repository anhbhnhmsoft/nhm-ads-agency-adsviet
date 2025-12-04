import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';
import { _UserRole } from '@/lib/types/constants';

export type EmployeeListItem = {
    id: string;
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
    manager_id?: string | null;
    employee_id?: string | null;
}>

export type UserOption = {
    id: string;
    name: string;
    username: string;
};

export type CustomerListItem = {
    id: string;
    name: string;
    username: string;
    email?: string | null;
    telegram_id?: string | null;
    phone: string | null;
    disabled: boolean;
    role: _UserRole;
    using_telegram: boolean;
    email_verified_at?: string | null;
    referral_code: string;
    wallet_balance?: number;
    wallet_status?: number;
    owner?: {
        id?: string;
        username?: string;
        role?: _UserRole;
    } | null;
    manager?: {
        id?: string;
        username?: string;
    } | null;
}
export type CustomerListPagination = LaravelPaginator<CustomerListItem>;

export type Employee = {
    id: string;
    name: string;
    username: string;
    phone: string | null;
    role: number;
    disabled: boolean;
}

export type EmployeeFormData = {
    id?: string;
    name: string;
    username: string;
    password?: string;
    phone?: string | null;
    role: number;
    disabled: boolean;
}

export type Manager = {
    id: string;
    name: string;
    username: string;
}

export type EmployeeForAssignment = {
    id: string;
    name: string;
    username: string;
    assigned: boolean;
}

export interface WalletData {
    id: string;
    user_id: string;
    balance: number;
    status: number;
}