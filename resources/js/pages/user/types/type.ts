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
    role_name?: string;
}

export type EmployeeListPagination = LaravelPaginator<EmployeeListItem>;

export type EmployeeListQuery = BaseSearchRequest<{
    keyword?: string;
}>

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
