import { _PlatformType } from '@/lib/types/constants';
import { BaseSearchRequest, LaravelPaginator } from '@/lib/types/type';

export type ServicePackageOption = {
    key: string;
    type: 'boolean' | 'number'; // Chỉ chấp nhận 'boolean' hoặc 'number'
    label: string;
};
// TypeScript/JavaScript (Dùng cho Frontend hoặc Node.js)
export type CreateServicePackageForm = {
    name: string;
    description: string | null;
    platform: _PlatformType;
    features: {
        key: string;
        value: boolean | number | null;
    }[];
    open_fee: string;
    range_min_top_up: string;
    top_up_fee: string;
    set_up_time: string;
    disabled: boolean;
};

export type ServicePackageFeatureValue = {
    key: string;
    value: boolean | number; // Giá trị có thể là boolean (true/false) hoặc số (60)
};

export type ServicePackageItem = {
    id: string;
    name: string;
    platform: _PlatformType;
    features: ServicePackageFeatureValue[];
    open_fee: string;
    top_up_fee: string;
    set_up_time: number;
    disabled: boolean;
    description: string;
    range_min_top_up: string;
};

export type ServicePackageListQuery = BaseSearchRequest<{
    keyword?: string;
}>;

export type ServicePackagePagination = LaravelPaginator<ServicePackageItem>;
