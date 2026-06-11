import type { ServicePackageFeatureValue } from '@/pages/service-package/types/type';

export type MonthlySpendingFee = {
    range: string;
    fee_percent: string;
};

export type ServicePackage = {
    id: string;
    name: string;
    description: string;
    platform: number;
    payment_type: 'prepay' | 'postpay';
    can_use_postpay?: boolean;
    features: ServicePackageFeatureValue[];
    open_fee: string;
    top_up_fee: number;
    spending_fee: number | string;
    set_up_time: number;
    range_min_top_up: string;
    disabled: boolean;
    inventory_total_count?: number;
    inventory_available_count?: number;
    monthly_spending_fee_structure?: MonthlySpendingFee[];
};

export type PackagesProp =
    | ServicePackage[]
    | {
          data?: ServicePackage[];
      };

export type TimezoneOption = {
    value: string;
    label: string;
};

export type ServicePurchasePageProps = {
    packages: PackagesProp;
    wallet_balance: number;
    postpay_min_balance?: number;
    meta_timezones?: TimezoneOption[];
    google_timezones?: TimezoneOption[];
};
