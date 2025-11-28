import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FacebookIcon from '@/images/facebook_icon.png';
import GoogleIcon from '@/images/google_icon.png';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import { useServicePurchaseForm } from '@/pages/service-purchase/hooks/use-form';
import type { ServicePackage, ServicePurchasePageProps } from '@/pages/service-purchase/types/type';
import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    Calculator,
    CheckCircle,
    DollarSign,
    Info,
    Search,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

const normalizeCurrencyInput = (value: string): string => {
    if (!value) return '';
    return value.replace(/\./g, '').replace(',', '.').trim();
};

const parseCurrencyInput = (value: string): number => {
    const normalized = normalizeCurrencyInput(value);
    if (!normalized) return 0;
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
};

const ServicePurchaseIndex = ({ packages, wallet_balance }: ServicePurchasePageProps) => {
    const { t } = useTranslation();
    const [selectedPackage, setSelectedPackage] = useState<ServicePackage | null>(null);
    const [topUpAmount, setTopUpAmount] = useState<string>('');
    const [budget, setBudget] = useState<string>('');
    const [showCalculator, setShowCalculator] = useState(false);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [metaEmail, setMetaEmail] = useState<string>('');
    const [displayName, setDisplayName] = useState<string>('');

    const packageList = useMemo<ServicePackage[]>(() => {
        if (Array.isArray(packages)) {
            return packages as ServicePackage[];
        }
        if (packages && Array.isArray((packages as { data?: ServicePackage[] }).data)) {
            return (packages as { data?: ServicePackage[] }).data as ServicePackage[];
        }
        return [];
    }, [packages]);

    const { form: purchaseForm, submit: submitPurchase } = useServicePurchaseForm();

    // Filter packages
    const filteredPackages = useMemo(() => {
        if (!searchQuery.trim()) return packageList;
        const query = searchQuery.toLowerCase();
        return packageList.filter(
            (pkg) =>
                pkg.name.toLowerCase().includes(query) ||
                pkg.description.toLowerCase().includes(query)
        );
    }, [packageList, searchQuery]);

    // Get platform info
    const getPlatformInfo = (platform: number) => {
        switch (platform) {
            case _PlatformType.GOOGLE:
                return {
                    name: 'Google Ads',
                    icon: <img src={GoogleIcon} alt="Google" className="w-8 h-8" />,
                    color: 'bg-blue-500',
                };
            case _PlatformType.META:
                return {
                    name: 'Facebook Ads',
                    icon: <img src={FacebookIcon} alt="Facebook" className="w-8 h-8" />,
                    color: 'bg-blue-600',
                };
            default:
                return {
                    name: 'Other',
                    icon: <DollarSign className="w-8 h-8" />,
                    color: 'bg-gray-500',
                };
        }
    };

    // Format currency
    const formatUSD = (amount: number | string) => {
        const num = typeof amount === 'string' ? parseFloat(amount) : amount;
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'USD',
        }).format(num);
    };

    const formatUSDT = (amount: number) => {
        return new Intl.NumberFormat('vi-VN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount) + ' USDT';
    };

    // Calculate service fee
    const calculateServiceFee = (topUpAmount: number, feePercent: number) => {
        return (topUpAmount * feePercent) / 100;
    };

    // Validate top-up amount
    const validateTopUpAmount = (amount: string) => {
        if (!amount) return null;
        const numAmount = parseCurrencyInput(amount);
        if (numAmount <= 0) {
            return t('service_purchase.invalid_amount');
        }
        return null;
    };

    // Validate budget
    const validateBudget = (amount: string) => {
        if (!amount) return null;
        const numAmount = parseCurrencyInput(amount);
        if (numAmount <= 0) {
            return t('service_purchase.budget_invalid');
        }
        if (numAmount < 50) {
            return t('service_purchase.budget_min_required', { min: 50 });
        }
        return null;
    };

    // Calculate total cost: open fee + top-up + service fee
    const calculateTotalCost = (pkg: ServicePackage, topUpAmountStr: string) => {
        const topUpNum = parseCurrencyInput(topUpAmountStr);
        const openFee = parseFloat(pkg.open_fee);
        const serviceFee = topUpNum > 0 ? calculateServiceFee(topUpNum, pkg.top_up_fee) : 0;
        const totalCost = openFee + topUpNum + serviceFee;
        return { serviceFee, totalCost, openFee, topUpNum };
    };

    // Handle purchase
    const handlePurchase = () => {
        if (!selectedPackage) return;

        const sanitizedTopUp = normalizeCurrencyInput(topUpAmount);
        const payloadTopUp = sanitizedTopUp ? sanitizedTopUp : '0';
        const topUpNum = parseCurrencyInput(topUpAmount);
        const { totalCost } = calculateTotalCost(selectedPackage, topUpAmount);

        if (wallet_balance < totalCost) {
            alert(t('service_purchase.insufficient_balance'));
            return;
        }

        if (topUpAmount && validateTopUpAmount(topUpAmount)) {
            alert(validateTopUpAmount(topUpAmount));
            return;
        }

        if (budget && validateBudget(budget)) {
            alert(validateBudget(budget));
            return;
        }

        const sanitizedBudget = normalizeCurrencyInput(budget);
        const payloadBudget = sanitizedBudget ? sanitizedBudget : '0';

        submitPurchase(selectedPackage.id, payloadTopUp, metaEmail, displayName, payloadBudget, () => {
            setSelectedPackage(null);
            setTopUpAmount('');
            setBudget('');
            setShowCalculator(false);
            setMetaEmail('');
            setDisplayName('');
        });
    };

    const featureLabelMap = useMemo<Record<string, string>>(() => ({
        meta_new_bm: t('service_purchase.feature_labels.meta_new_bm'),
        meta_multibrand_support: t('service_purchase.feature_labels.meta_multibrand_support'),
        meta_fanpage_attached: t('service_purchase.feature_labels.meta_fanpage_attached'),
        meta_timezone_id: t('service_purchase.feature_labels.meta_timezone_id'),
        new_account: t('service_purchase.feature_labels.new_account'),
        guarantee: t('service_purchase.feature_labels.guarantee'),
        support_247: t('service_purchase.feature_labels.support_247'),
        google_trust_score_high: t('service_purchase.feature_labels.google_trust_score_high'),
    }), [t]);

    const renderFeatureText = (feature: { key: string; value: boolean | number }) => {
        const label = featureLabelMap[feature.key] || feature.key;
        if (typeof feature.value === 'boolean') {
            return feature.value ? label : null;
        }

        switch (feature.key) {
            case 'guarantee':
                return t('service_purchase.feature_values.guarantee', { value: feature.value });
            case 'meta_timezone_id':
                return t('service_purchase.feature_values.meta_timezone_id', { value: feature.value });
            default:
                return `${label}: ${feature.value}`;
        }
    };

    // Render service card
    const renderServiceCard = (pkg: ServicePackage) => {
        const platformInfo = getPlatformInfo(pkg.platform);
        const features = pkg.features || [];

        return (
            <Card key={pkg.id} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-3">
                            <div className="text-3xl">{platformInfo.icon}</div>
                            <div>
                                <CardTitle className="text-lg">{pkg.name}</CardTitle>
                                <Badge className={`${platformInfo.color} text-white text-xs mt-1`}>
                                    {platformInfo.name}
                                </Badge>
                            </div>
                        </div>
                        {!pkg.disabled && (
                            <Badge variant="default">Active</Badge>
                        )}
                    </div>
                </CardHeader>

                <CardContent className="space-y-4">
                    <p className="text-gray-600 text-sm">{pkg.description}</p>

                    {/* Pricing Info */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="text-center p-3 bg-blue-50 rounded-lg">
                            <div className="text-xl font-bold text-blue-600">
                                {formatUSDT(parseFloat(pkg.open_fee))}
                            </div>
                            <div className="text-xs text-gray-600">
                                {t('service_purchase.account_opening_fee')}
                            </div>
                        </div>
                        <div className="text-center p-3 bg-green-50 rounded-lg">
                            <div className="sm:text-xl text-base font-bold text-green-600">
                                {pkg.top_up_fee}%
                            </div>
                            <div className="text-xs text-gray-600">
                                {t('service_purchase.service_fee_pct')}
                            </div>
                        </div>
                    </div>

                    {/* Limits */}
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-gray-500">{t('service_purchase.min_top_up')}:</span>
                            <span className="font-medium ml-2">{formatUSD(pkg.range_min_top_up)}</span>
                        </div>
                        <div>
                            <span className="text-gray-500">{t('service_purchase.setup_time')}:</span>
                            <span className="font-medium ml-2">{pkg.set_up_time} {t('service_purchase.hours')}</span>
                        </div>
                    </div>

                    {/* Features */}
                    {features.length > 0 && (
                        <div>
                            <div className="text-sm font-medium text-gray-700 mb-2">
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

                    <Button
                        className="w-full"
                        onClick={() => setSelectedPackage(pkg)}
                        disabled={pkg.disabled}
                    >
                        <ShoppingCart className="h-4 w-4 mr-2" />
                        {t('service_purchase.select_service')}
                    </Button>
                </CardContent>
            </Card>
        );
    };

    // Render order form
    const renderOrderForm = () => {
        if (!selectedPackage) return null;

        const platformInfo = getPlatformInfo(selectedPackage.platform);
        const topUpError = topUpAmount ? validateTopUpAmount(topUpAmount) : null;
        const { serviceFee, totalCost, openFee, topUpNum } = calculateTotalCost(selectedPackage, topUpAmount);
        const minTopUpAmount = Number(selectedPackage.range_min_top_up || '0');
        const hasInsufficientBalance = wallet_balance < totalCost;
        const showAccountInfo =
            selectedPackage.platform === _PlatformType.META || selectedPackage.platform === _PlatformType.GOOGLE;
        const accountInfoTitle =
            selectedPackage.platform === _PlatformType.META
                ? t('service_purchase.meta_account_info', { defaultValue: 'Thông tin tài khoản Meta' })
                : t('service_purchase.google_account_info', { defaultValue: 'Thông tin tài khoản Google' });

        return (
            <Card className="max-w-2xl mx-auto">
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="flex items-center gap-2">
                            <span className="text-2xl">{platformInfo.icon}</span>
                            {t('service_purchase.order_summary')}: {selectedPackage.name}
                        </CardTitle>
                        <Button className="hidden sm:block" variant="outline" onClick={() => setSelectedPackage(null)}>
                            {t('service_purchase.back_to_services')}
                        </Button>
                    </div>
                </CardHeader>

                <CardContent className="space-y-6">
                    {/* Wallet Balance */}
                    <div className="p-4 bg-linear-to-r from-green-50 to-blue-50 rounded-lg">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Wallet className="h-5 w-5 text-green-600" />
                                <span className="font-medium">{t('service_purchase.wallet_balance')}:</span>
                            </div>
                            <div className="sm:text-xl text-base font-bold text-green-600">
                                {formatUSDT(wallet_balance)}
                            </div>
                        </div>
                    </div>

                    {/* Service Details */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="p-3 bg-gray-50 rounded-lg">
                            <div className="text-sm text-gray-600">{t('service_purchase.account_opening_fee')}</div>
                            <div className="sm:text-lg text-base font-bold">{formatUSDT(openFee)}</div>
                        </div>
                        <div className="p-3 bg-gray-50 rounded-lg">
                            <div className="text-sm text-gray-600">{t('service_purchase.service_fee_pct')}</div>
                            <div className="sm:text-lg text-base font-bold">{selectedPackage.top_up_fee}%</div>
                        </div>
                    </div>

                    {/* Account Info (Meta / Google) */}
                    {showAccountInfo && (
                        <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                            <div className="font-medium text-gray-800">{accountInfoTitle}</div>
                            <div className="space-y-2">
                                <Label htmlFor="metaEmail">
                                    {t('service_purchase.meta_email')}:
                                </Label>
                                <Input
                                    id="metaEmail"
                                    type="email"
                                    placeholder="abc123@gmail.com"
                                    value={metaEmail}
                                    onChange={(e) => setMetaEmail(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="displayName">
                                    {t('service_purchase.display_name')}:
                                </Label>
                                <Input
                                    id="displayName"
                                    type="text"
                                    placeholder="abc"
                                    value={displayName}
                                    onChange={(e) => setDisplayName(e.target.value)}
                                />
                            </div>
                        </div>
                    )}

                    {/* Budget */}
                    <div className="space-y-2">
                        <Label htmlFor="budget">
                            {t('service_purchase.budget')} ({t('service_purchase.required')})
                        </Label>
                        <Input
                            id="budget"
                            type="number"
                            placeholder="0.00"
                            value={budget}
                            onChange={(e) => setBudget(e.target.value)}
                            step="0.01"
                            min="0"
                            max="50"
                            required
                        />
                        {budget && validateBudget(budget) && (
                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                <AlertTriangle className="h-4 w-4" />
                                {validateBudget(budget)}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {t('service_purchase.budget_hint', { min: 50 })}
                        </p>
                    </div>

                    {/* Top-up Amount */}
                    <div className="space-y-2">
                        <Label htmlFor="topUpAmount">
                            {t('service_purchase.top_up_amount')} ({t('service_purchase.optional')})
                        </Label>
                        <div className="flex gap-2">
                            <Input
                                id="topUpAmount"
                                type="number"
                                placeholder={`${Math.ceil(
                                    Number(selectedPackage.range_min_top_up || '0')
                                )} USDT`}
                                value={topUpAmount}
                                onChange={(e) => setTopUpAmount(e.target.value)}
                                step="1"
                            />
                            <Button
                                variant="outline"
                                onClick={() => setShowCalculator(!showCalculator)}
                            >
                                <Calculator className="h-4 w-4" />
                            </Button>
                        </div>
                        {topUpError && (
                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                <AlertTriangle className="h-4 w-4" />
                                {topUpError}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {t('service_purchase.top_up_amount_note')}, 
                            {minTopUpAmount > 0
                                ? t('service_purchase.min_top_up_hint', {
                                      amount: formatUSD(minTopUpAmount),
                                  })
                                : t('service_purchase.top_up_amount_note')}
                        </p>
                    </div>

                    {/* Fee Calculator */}
                    {(showCalculator || topUpAmount) && (
                        <div className="p-4 bg-blue-50 rounded-lg space-y-3">
                            <div className="font-medium text-blue-800">{t('service_purchase.calculate_fee')}:</div>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="flex justify-between">
                                    <span>{t('service_purchase.account_opening_fee')}:</span>
                                    <span className="font-medium">{formatUSDT(openFee)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('service_purchase.top_up')}:</span>
                                    <span className="font-medium">{formatUSDT(topUpNum)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>
                                        {t('service_purchase.service_fee')} ({selectedPackage.top_up_fee}%):
                                    </span>
                                    <span className="font-medium">{formatUSDT(serviceFee)}</span>
                                </div>
                            </div>
                            <div className="border-t pt-2">
                                <div className="flex justify-between font-bold sm:text-lg text-md">
                                    <span>{t('service_purchase.total_cost')}:</span>
                                    <span className={hasInsufficientBalance ? 'text-red-600' : 'text-green-600'}>
                                        {formatUSDT(totalCost)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Insufficient Balance Warning */}
                    {hasInsufficientBalance && (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div className="flex items-center gap-2 text-red-800">
                                <AlertTriangle className="h-4 w-4" />
                                <span className="font-medium">{t('service_purchase.insufficient_balance')}</span>
                            </div>
                            <p className="text-red-700 text-sm mt-1">{t('service_purchase.please_top_up_wallet')}</p>
                        </div>
                    )}

                    {/* Purchase Button */}
                    <Button
                        className="w-full mb-3"
                        size="lg"
                        onClick={handlePurchase}
                        disabled={hasInsufficientBalance || !!topUpError || !!validateBudget(budget) || !budget || purchaseForm.processing}
                    >
                        <ShoppingCart className="h-4 w-4 mr-2" />
                        {purchaseForm.processing
                            ? t('common.processing')
                            : `${t('service_purchase.purchase_now')} - ${formatUSDT(totalCost)}`}
                    </Button>
                    <Button className="sm:hidden block w-full" variant="outline" onClick={() => setSelectedPackage(null)}>
                            {t('service_purchase.back_to_services')}
                    </Button>
                </CardContent>
            </Card>
        );
    };

    return (
        <AppLayout>
            <Head title={t('service_purchase.service_selection')} />
            <div className="space-y-6">
                <div className="flex sm:flex-row flex-col items-center justify-between">
                    <h1 className="sm:text-3xl text-xl sm:mb-0 mb-4 font-bold text-gray-900">{t('service_purchase.service_selection')}</h1>
                    <div className="flex items-center gap-2 px-4 py-2 bg-green-50 rounded-lg">
                        <Wallet className="h-5 w-5 text-green-600" />
                        <span className="text-sm text-gray-600">{t('service_purchase.wallet_balance')}:</span>
                        <span className="font-bold text-green-600">{formatUSDT(wallet_balance)}</span>
                    </div>
                </div>

                {selectedPackage ? (
                    renderOrderForm()
                ) : (
                    <>
                        <div className="flex items-center gap-2 mb-4">
                            <Info className="h-5 w-5 text-blue-500" />
                            <span className="text-gray-600">{t('service_purchase.info_message')}</span>
                        </div>

                        {/* Search */}
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder={t('service_purchase.search_placeholder')}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10"
                            />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {filteredPackages.map(renderServiceCard)}
                        </div>

                        {filteredPackages.length === 0 && (
                            <div className="text-center py-12">
                                <p className="text-gray-500">{t('service_purchase.no_services_found')}</p>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
};

export default ServicePurchaseIndex;

