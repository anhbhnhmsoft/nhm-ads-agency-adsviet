import { Avatar, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import FacebookIcon from '@/images/facebook_icon.png';
import GoogleIcon from '@/images/google_icon.png';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import { ServicePackageItem } from '@/pages/service-package/types/type';
import { PackageOpen, CheckCircle, Search } from 'lucide-react';
import { ReactNode, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    packages: { data: ServicePackageItem[] };
};

const ServicePackageList = ({ packages }: Props) => {
    const { t } = useTranslation();
    const [searchQuery, setSearchQuery] = useState('');
    const [platformFilter, setPlatformFilter] = useState<string>('all');

    const packageList = useMemo(() => {
        if (packages?.data) return packages.data;
        if (Array.isArray(packages)) return packages as ServicePackageItem[];
        return [];
    }, [packages]);

    const filteredPackages = useMemo(() => {
        let filtered = packageList;
        if (platformFilter !== 'all') {
            const platformNum = parseInt(platformFilter);
            filtered = filtered.filter((pkg) => pkg.platform === platformNum);
        }
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(
                (pkg) =>
                    pkg.name.toLowerCase().includes(query) ||
                    (pkg.description && pkg.description.toLowerCase().includes(query)),
            );
        }
        return filtered;
    }, [packageList, searchQuery, platformFilter]);

    const getPlatformInfo = (platform: number) => {
        switch (platform) {
            case _PlatformType.GOOGLE:
                return {
                    name: 'Google Ads',
                    icon: <Avatar><AvatarImage src={GoogleIcon} /></Avatar>,
                };
            case _PlatformType.META:
                return {
                    name: 'Facebook Ads',
                    icon: <Avatar><AvatarImage src={FacebookIcon} /></Avatar>,
                };
            default:
                return { name: 'Other', icon: null };
        }
    };

    const formatUSD = (amount: number | string) => {
        const num = typeof amount === 'string' ? parseFloat(amount) : amount;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(num);
    };

    const getEffectiveTopUpFeePercent = (pkg: ServicePackageItem) =>
        pkg.billing_source === 'customer_card' ? 0 : Number(pkg.top_up_fee || 0);

    const getEffectiveSpendingFeePercent = (pkg: ServicePackageItem) => {
        const spendingFee = Number(pkg.spending_fee || 0);
        if (spendingFee > 0) return spendingFee;
        return pkg.billing_source === 'customer_card' ? Number(pkg.top_up_fee || 0) : 0;
    };

    const featureLabelMap = useMemo<Record<string, string>>(
        () => ({
            meta_new_bm: t('service_purchase.feature_labels.meta_new_bm'),
            meta_multibrand_support: t('service_purchase.feature_labels.meta_multibrand_support'),
            meta_fanpage_attached: t('service_purchase.feature_labels.meta_fanpage_attached'),
            meta_timezone_id: t('service_purchase.feature_labels.meta_timezone_id'),
            new_account: t('service_purchase.feature_labels.new_account'),
            guarantee: t('service_purchase.feature_labels.guarantee'),
            support_247: t('service_purchase.feature_labels.support_247'),
            google_trust_score_high: t('service_purchase.feature_labels.google_trust_score_high'),
        }),
        [t],
    );

    const renderFeatureText = (feature: { key: string; value: boolean | number }) => {
        const label = featureLabelMap[feature.key] || feature.key;
        if (typeof feature.value === 'boolean') return feature.value ? label : null;
        switch (feature.key) {
            case 'guarantee':
                return t('service_purchase.feature_values.guarantee', { value: feature.value });
            case 'meta_timezone_id':
                return t('service_purchase.feature_values.meta_timezone_id', { value: feature.value });
            default:
                return `${label}: ${feature.value}`;
        }
    };

    return (
        <>
            <div className="space-y-6">
                <h1 className="text-xl font-bold text-gray-900 sm:text-3xl">
                    {t('menu.service_packages_list', { defaultValue: 'Danh sách gói dịch vụ' })}
                </h1>

                {packageList.length > 0 ? (
                    <>
                        {/* Filter & Search */}
                        <div className="flex flex-col gap-4 sm:flex-row">
                            <div className="w-full sm:w-48">
                                <Select value={platformFilter} onValueChange={setPlatformFilter}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('service_purchase.filter_platform', { defaultValue: 'Lọc theo nền tảng' })} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('service_purchase.filter_all', { defaultValue: 'Tất cả' })}</SelectItem>
                                        <SelectItem value={String(_PlatformType.GOOGLE)}>Google Ads</SelectItem>
                                        <SelectItem value={String(_PlatformType.META)}>Meta Ads</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    placeholder={t('service_purchase.search_placeholder')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>

                        <Separator className="my-4" />

                        {/* Package Cards */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {filteredPackages.map((pkg) => {
                                const platformInfo = getPlatformInfo(pkg.platform);
                                const features = pkg.features || [];

                                return (
                                    <Card key={pkg.id} className="flex flex-col">
                                        <CardHeader>
                                            <div className="flex items-start justify-between">
                                                <div className="flex min-w-0 items-start gap-3">
                                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center">
                                                        {platformInfo.icon}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <CardTitle className="text-lg leading-snug">{pkg.name}</CardTitle>
                                                        <Badge className="mt-1 bg-[#4285f4] text-xs text-white">{platformInfo.name}</Badge>
                                                    </div>
                                                </div>
                                                <Badge variant={pkg.disabled ? 'destructive' : 'default'} className="ml-2 shrink-0">
                                                    {pkg.disabled ? t('common.disabled') : t('common.active')}
                                                </Badge>
                                            </div>
                                        </CardHeader>

                                        <CardContent className="flex flex-1 flex-col space-y-4">
                                            {pkg.description && (
                                                <p className="text-sm text-gray-600">{pkg.description}</p>
                                            )}

                                            {/* Payment Type */}
                                            <div>
                                                <Badge variant={pkg.payment_type === 'postpay' ? 'secondary' : 'default'}>
                                                    {pkg.payment_type === 'postpay'
                                                        ? t('service_packages.payment_type_postpay')
                                                        : t('service_packages.payment_type_prepay')}
                                                </Badge>
                                            </div>

                                            {/* Pricing */}
                                            <div className="grid grid-cols-3 gap-2">
                                                <div className="rounded-lg bg-orange-50 p-2 text-center">
                                                    <div className="break-all text-sm font-bold text-[#4285f4] sm:text-base">
                                                        {formatUSD(parseFloat(pkg.open_fee))}
                                                    </div>
                                                    <div className="mt-1 text-[10px] leading-tight text-gray-600 sm:text-xs">
                                                        {t('service_purchase.account_opening_fee')}
                                                    </div>
                                                </div>
                                                <div className="rounded-lg bg-green-50 p-2 text-center">
                                                    <div className="text-base font-bold text-green-600 sm:text-lg">
                                                        {getEffectiveTopUpFeePercent(pkg)}%
                                                    </div>
                                                    <div className="mt-1 text-[10px] leading-tight text-gray-600 sm:text-xs">
                                                        {t('service_purchase.service_fee_pct')}
                                                    </div>
                                                </div>
                                                <div className="rounded-lg bg-blue-50 p-2 text-center">
                                                    <div className="text-base font-bold text-blue-600 sm:text-lg">
                                                        {getEffectiveSpendingFeePercent(pkg)}%
                                                    </div>
                                                    <div className="mt-1 text-[10px] leading-tight text-gray-600 sm:text-xs">
                                                        {t('service_purchase.spending_fee_pct')}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Limits */}
                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <span className="text-gray-500">{t('service_purchase.min_top_up')}:</span>
                                                    <span className="ml-2 font-medium">{formatUSD(pkg.range_min_top_up)}</span>
                                                </div>
                                                <div>
                                                    <span className="text-gray-500">{t('service_purchase.setup_time')}:</span>
                                                    <span className="ml-2 font-medium">{pkg.set_up_time} {t('service_purchase.hours')}</span>
                                                </div>
                                            </div>

                                            {/* Inventory */}
                                            {(pkg.inventory_total_count || 0) > 0 && (
                                                <div className="rounded-md border bg-muted/30 p-3 text-sm">
                                                    <div className="font-medium">
                                                        {t('service_purchase.account_inventory_ready', { defaultValue: 'Kho tài khoản tự động' })}
                                                    </div>
                                                    <div className="text-muted-foreground">
                                                        {t('service_purchase.account_inventory_available', {
                                                            defaultValue: '{{available}} / {{total}} tài khoản sẵn sàng',
                                                            available: pkg.inventory_available_count || 0,
                                                            total: pkg.inventory_total_count || 0,
                                                        })}
                                                    </div>
                                                </div>
                                            )}

                                            {/* Features */}
                                            {features.length > 0 && (
                                                <div>
                                                    <div className="mb-2 text-sm font-medium text-gray-700">
                                                        {t('service_purchase.features')}:
                                                    </div>
                                                    <div className="space-y-1">
                                                        {features.map((feature, index) => {
                                                            const displayText = renderFeatureText(feature);
                                                            if (!displayText) return null;
                                                            return (
                                                                <div key={index} className="flex items-center gap-2 text-sm">
                                                                    <CheckCircle className="h-3 w-3 text-green-500" />
                                                                    <span className="text-gray-600">{displayText}</span>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        {filteredPackages.length === 0 && (
                            <div className="py-12 text-center">
                                <p className="text-gray-500">{t('service_purchase.no_services_found')}</p>
                            </div>
                        )}
                    </>
                ) : (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <PackageOpen />
                            </EmptyMedia>
                            <EmptyTitle>{t('service_packages.empty_title')}</EmptyTitle>
                            <EmptyDescription>{t('service_packages.empty_description')}</EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                )}
            </div>
        </>
    );
};

ServicePackageList.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'Xem gói dịch vụ' }]} children={page} />
);

export default ServicePackageList;
