import type { LaravelPaginator } from '@/lib/types/type';
import type { ServicePackageFeatureValue } from '@/pages/service-package/types/type';

export type ServicePackage = {
    id: string;
    name: string;
    description: string;
    platform: number;
    features: ServicePackageFeatureValue[];
    open_fee: string;
    top_up_fee: number;
    set_up_time: number;
    range_min_top_up: string;
    disabled: boolean;
};

export type PackagesProp = ServicePackage[] | {
    data?: ServicePackage[];
};

export type ServicePurchasePageProps = {
    packages: PackagesProp;
    wallet_balance: number;
};


