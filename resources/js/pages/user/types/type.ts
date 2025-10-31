import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';
import { _UserRole } from '@/lib/types/constants';

export type EmployeeListItem = {
    id: number;
    username: string;
    phone: string | null;
    disabled: boolean;
    referral_code: string;
    role: _UserRole;
    role_name: string;
}

export type EmployeeListPagination = LaravelPaginator<EmployeeListItem>;


export type EmployeeListQuery = BaseSearchRequest<{
    keyword?: string;
}>
