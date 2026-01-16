import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FacebookIcon from '@/images/facebook_icon.png';
import GoogleIcon from '@/images/google_icon.png';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import { useServicePurchaseForm, type AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { AccountForm } from '@/pages/service-purchase/components/AccountForm';
import type { ServicePackage, ServicePurchasePageProps } from '@/pages/service-purchase/types/type';
import { Head, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Calculator,
    CheckCircle,
    DollarSign,
    Info,
    Plus,
    Search,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useEffect, useMemo, useRef, useState } from 'react';
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

const ServicePurchaseIndex = ({ packages, wallet_balance, postpay_min_balance, meta_timezones = [], google_timezones = [], postpay_permissions = {} }: ServicePurchasePageProps) => {
    const { t } = useTranslation();
    const page = usePage();
    const postpayMinBalance = typeof postpay_min_balance === 'number' ? postpay_min_balance : 200;
    const [selectedPackage, setSelectedPackage] = useState<ServicePackage | null>(null);
    const [showCalculator, setShowCalculator] = useState(false);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [platformFilter, setPlatformFilter] = useState<string>('all');
    const [touchedFields, setTouchedFields] = useState<{ topUpAmount?: boolean; budget?: boolean }>({});
    const [accounts, setAccounts] = useState<AccountFormData[]>([
        {
            meta_email: '',
            display_name: '',
            bm_ids: [],
            fanpages: [],
            websites: [],
            timezone_bm: '',
            asset_access: 'full_asset',
        },
    ]);
    const previousPlatformRef = useRef<number | undefined>(undefined);
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

    useEffect(() => {
        const currentLocale = (page.props as any)?.locale || 'unknown';
        console.log('[Frontend] ServicePurchaseIndex render - Locale Debug', {
            current_locale: currentLocale,
            form_errors: purchaseForm.errors,
            has_meta_email_error: !!(purchaseForm.errors.meta_email || purchaseForm.errors['accounts.0.meta_email']),
            meta_email_error_value: purchaseForm.errors.meta_email || purchaseForm.errors['accounts.0.meta_email'],
        });
    }, [purchaseForm.errors, page.props]);

    const {
        top_up_amount,
        budget,
        meta_email,
        display_name,
        bm_id,
        info_fanpage,
        info_website,
        payment_type,
        asset_access,
    } = purchaseForm.data;

    const paymentType: 'prepay' | 'postpay' = (payment_type as 'prepay' | 'postpay') || 'prepay';
    const topUpAmount = top_up_amount || '';
    const budgetValue = budget || '';
    const [postpayDays, setPostpayDays] = useState<number>(7);

    useEffect(() => {
        const currentPlatform = selectedPackage?.platform;

        if (previousPlatformRef.current !== currentPlatform) {
            previousPlatformRef.current = currentPlatform;

            if (currentPlatform === _PlatformType.GOOGLE) {
                purchaseForm.setData('info_fanpage', '');
                purchaseForm.setData('info_website', '');
            }

            setAccounts([
                {
                    meta_email: '',
                    display_name: '',
                    bm_ids: [],
                    fanpages: currentPlatform === _PlatformType.META ? [] : [],
                    websites: [],
                    timezone_bm: '',
                    asset_access: 'full_asset',
                },
            ]);

            setTimeout(() => {
                setTouchedFields({});
            }, 0);
        }
    }, [selectedPackage?.platform, purchaseForm]);

    // Reset payment_type về 'prepay' nếu package không cho phép trả sau
    useEffect(() => {
        if (selectedPackage) {
            const isPostpayAllowed = postpay_permissions[selectedPackage.id] === true;
            // Nếu đang chọn trả sau nhưng package không cho phép => reset về trả trước
            if (paymentType === 'postpay' && !isPostpayAllowed) {
                purchaseForm.setData('payment_type', 'prepay');
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedPackage?.id, postpay_permissions, paymentType]);

    // Filter packages
    const filteredPackages = useMemo(() => {
        let filtered = packageList;

        // Filter by platform
        if (platformFilter !== 'all') {
            const platformNum = parseInt(platformFilter);
            filtered = filtered.filter((pkg) => pkg.platform === platformNum);
        }

        // Filter by search query
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(
                (pkg) =>
                    pkg.name.toLowerCase().includes(query) ||
                    pkg.description.toLowerCase().includes(query)
            );
        }

        return filtered;
    }, [packageList, searchQuery, platformFilter]);

    // Get platform info
    const getPlatformInfo = (platform: number) => {
        switch (platform) {
            case _PlatformType.GOOGLE:
                return {
                    name: 'Google Ads',
                    icon: <img src={GoogleIcon} alt="Google" className="w-8 h-8" />,
                    color: 'bg-[#4285f4]',
                };
            case _PlatformType.META:
                return {
                    name: 'Facebook Ads',
                    icon: <img src={FacebookIcon} alt="Facebook" className="w-8 h-8" />,
                    color: 'bg-[#4285f4]',
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

    const parseRange = (range: string) => {
        const trimmed = (range || '').trim();
        if (!trimmed) return null;
        if (trimmed.includes('-')) {
            const [minStr, maxStr] = trimmed.split('-').map((s) => s.trim());
            const min = parseFloat(minStr);
            const max = parseFloat(maxStr);
            if (Number.isFinite(min) && Number.isFinite(max)) return { min, max };
        } else if (trimmed.endsWith('+')) {
            const min = parseFloat(trimmed.replace('+', '').trim());
            if (Number.isFinite(min)) return { min, max: null };
        }
        return null;
    };

    const getMonthlyFeePercent = (amount: number, tiers: { range: string; fee_percent: string }[]) => {
        for (const tier of tiers) {
            const parsed = parseRange(tier.range);
            const feePercent = parseFloat(tier.fee_percent);
            if (!parsed || !Number.isFinite(feePercent)) continue;
            const { min, max } = parsed;
            const match = amount >= min && (max === null || amount <= max);
            if (match) return feePercent;
        }
        return null;
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

    // Calculate total cost: open fee + top-up + service fee
    const calculateTotalCost = (pkg: ServicePackage, topUpAmountStr: string, paymentType: 'prepay' | 'postpay') => {
        const isPrepay = paymentType === 'prepay';
        const topUpNum = isPrepay ? parseCurrencyInput(topUpAmountStr) : 0;
        const openFee = parseFloat(pkg.open_fee);
        const chargeOpenFee = isPrepay ? openFee : 0; // Trả sau không thu phí mở tài khoản upfront
        const serviceFee = topUpNum > 0 ? calculateServiceFee(topUpNum, pkg.top_up_fee) : 0;
        const totalCost = chargeOpenFee + topUpNum + serviceFee;
        return { serviceFee, totalCost, openFee, chargeOpenFee, topUpNum };
    };

    // Handle purchase
    const handlePurchase = () => {
        if (!selectedPackage) return;

        // Mark all fields as touched on submit
        setTouchedFields({ topUpAmount: true /*, budget: true */ });

        // Kiểm tra quyền trả sau - chỉ true mới được phép
        const isPostpayAllowed = selectedPackage
            ? (postpay_permissions[selectedPackage.id] === true)
            : false;

        if (paymentType === 'postpay') {
            if (!isPostpayAllowed) {
                alert(t('services.validation.postpay_not_allowed'));
                purchaseForm.setData('payment_type', 'prepay');
                return;
            }
            if (wallet_balance < postpayMinBalance) {
                alert(
                    t('service_purchase.postpay_min_wallet', {
                        defaultValue: 'Ví của bạn cần tối thiểu {{amount}} USDT để chọn thanh toán trả sau.',
                        amount: postpayMinBalance,
                    })
                );
                return;
            }
        }

        const isPrepay = paymentType === 'prepay';
        const sanitizedTopUp = isPrepay ? normalizeCurrencyInput(topUpAmount) : '';
        const payloadTopUp = sanitizedTopUp ? sanitizedTopUp : '0';
        const { totalCost } = calculateTotalCost(selectedPackage, topUpAmount, paymentType);

        if (wallet_balance < totalCost) {
            alert(t('service_purchase.insufficient_balance'));
            return;
        }

        if (isPrepay && topUpAmount && validateTopUpAmount(topUpAmount)) {
            alert(validateTopUpAmount(topUpAmount));
            return;
        }

        const payloadBudget = '0';

        const hasAccounts = accounts.some(
            acc => acc.meta_email || acc.display_name || (acc.bm_ids && acc.bm_ids.length > 0)
        );

        const bmMccConfig = {
            bm_id: bm_id || undefined,
            info_fanpage: info_fanpage || undefined,
            info_website: info_website || undefined,
            payment_type: paymentType,
            postpay_days: paymentType === 'postpay' ? postpayDays : undefined,
            asset_access: asset_access || 'full_asset',
        };

        submitPurchase(
            selectedPackage.id,
            payloadTopUp,
            meta_email,
            display_name,
            purchaseForm.data.timezone_bm,
            payloadBudget,
            bmMccConfig,
            hasAccounts ? accounts : undefined,
            () => {
                setSelectedPackage(null);
                setShowCalculator(false);
                setTouchedFields({});
                setAccounts([
                    {
                        meta_email: '',
                        display_name: '',
                        bm_ids: [],
                        fanpages: [],
                        websites: [],
                        timezone_bm: '',
                        asset_access: 'full_asset',
                    },
                ]);
                purchaseForm.reset();
                purchaseForm.setData({
                    payment_type: 'prepay',
                    budget: '0',
                    top_up_amount: '',
                    asset_access: 'full_asset',
                });
                setPostpayDays(7); // Reset về mặc định
            }
        );
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
                        <div className="text-center p-3 bg-orange-50 rounded-lg">
                            <div className="text-xl font-bold text-[#4285f4]">
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
                        <div className="min-h-48">
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
        const topUpError = touchedFields.topUpAmount && topUpAmount ? validateTopUpAmount(topUpAmount) : null;
        const { serviceFee, totalCost, chargeOpenFee, topUpNum } = calculateTotalCost(selectedPackage, topUpAmount, paymentType);
        const minTopUpAmount = Number(selectedPackage.range_min_top_up || '0');
        const hasInsufficientBalance = wallet_balance < totalCost;
        const showAccountInfo =
            selectedPackage.platform === _PlatformType.META || selectedPackage.platform === _PlatformType.GOOGLE;
        const accountInfoTitle =
            selectedPackage.platform === _PlatformType.META
                ? t('service_purchase.meta_account_info', { defaultValue: 'Thông tin tài khoản Meta' })
                : t('service_purchase.google_account_info', { defaultValue: 'Thông tin tài khoản Google' });
        const monthlySpendingTiers = selectedPackage.monthly_spending_fee_structure || [];
        const budgetNum = parseCurrencyInput(budgetValue);
        // Nếu budget = 0 hoặc không có, không tính monthly fee (unlimited)
        const monthlyFeePercent =
            paymentType === 'postpay' && monthlySpendingTiers.length > 0 && budgetNum > 0
                ? getMonthlyFeePercent(budgetNum, monthlySpendingTiers)
                : null;
        const monthlyFee = monthlyFeePercent ? (budgetNum * monthlyFeePercent) / 100 : 0;

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
                    <div className="p-4 bg-linear-to-r from-green-50 to-orange-50 rounded-lg">
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
                            <div className="sm:text-lg text-base font-bold">{formatUSDT(chargeOpenFee)}</div>
                            {paymentType === 'postpay' && (
                                <div className="text-xs text-amber-600">
                                    {t('service_purchase.postpay_open_fee_hint', { defaultValue: 'Phí mở tài khoản thu khi đối soát (không thu trước)' })}
                                </div>
                            )}
                        </div>
                        <div className="p-3 bg-gray-50 rounded-lg">
                            <div className="text-sm text-gray-600">{t('service_purchase.service_fee_pct')}</div>
                            <div className="sm:text-lg text-base font-bold">{selectedPackage.top_up_fee}%</div>
                        </div>
                    </div>

                    {showAccountInfo && (
                        <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                            <div className="flex items-center justify-between">
                                <div className="font-medium text-gray-800">{accountInfoTitle}</div>
                                {accounts.length < 3 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setAccounts([
                                                ...accounts,
                                                {
                                                    meta_email: '',
                                                    display_name: '',
                                                    bm_ids: [],
                                                    fanpages: selectedPackage.platform === _PlatformType.META ? [] : [],
                                                    websites: [],
                                                    timezone_bm: '',
                                                    asset_access: 'full_asset',
                                                },
                                            ]);
                                        }}
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t('service_purchase.add_account', { defaultValue: 'Thêm tài khoản' })}
                                    </Button>
                                )}
                            </div>

                            {/* Render accounts using AccountForm component */}
                            <div className="space-y-4">
                                {accounts.map((account, idx) => {
                                    const metaEmailError =
                                        (purchaseForm.errors as any)?.[`accounts.${idx}.meta_email`];

                                    return (
                                        <AccountForm
                                            key={idx}
                                            account={account}
                                            accountIndex={idx}
                                            platform={selectedPackage.platform}
                                            metaTimezones={meta_timezones}
                                            googleTimezones={google_timezones}
                                            metaEmailError={metaEmailError}
                                            onUpdate={(index, updater) => {
                                            setAccounts((prevAccounts) => {
                                                const newAccounts = [...prevAccounts];
                                                const currentAccount = newAccounts[index] || {
                                                    meta_email: '',
                                                    display_name: '',
                                                    bm_ids: [],
                                                    fanpages: [],
                                                    websites: [],
                                                    timezone_bm: '',
                                                    asset_access: 'full_asset',
                                                };
                                                if (typeof updater === 'function') {
                                                    newAccounts[index] = updater(currentAccount);
                                                } else {
                                                    newAccounts[index] = updater;
                                                }
                                                return newAccounts;
                                            });
                                        }}
                                            onRemove={(index) => {
                                                setAccounts(accounts.filter((_, i) => i !== index));
                                            }}
                                            canRemove={accounts.length > 1}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {/* Monthly spending tiers */}
                    {monthlySpendingTiers.length > 0 && (
                        <div className="space-y-3">
                            <div>
                                <p className="font-medium text-gray-800">
                                    {t('service_purchase.monthly_spending_title')}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {t('service_purchase.monthly_spending_description')}
                                </p>
                            </div>
                            <div className="rounded-lg border overflow-hidden">
                                <div className="grid grid-cols-[2fr_1fr] bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600">
                                    <span>
                                        {t('service_purchase.monthly_spending_spending_label')}
                                    </span>
                                    <span>
                                        {t('service_purchase.monthly_spending_fee_label')}
                                    </span>
                                </div>
                                <div className="divide-y">
                                    {monthlySpendingTiers.map((tier, index) => (
                                        <div
                                            key={`monthly-tier-display-${index}`}
                                            className="grid grid-cols-[2fr_1fr] px-4 py-2 text-sm text-gray-700"
                                        >
                                            <span>{tier.range}</span>
                                            <span className="font-medium">{tier.fee_percent}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Payment type */}
                    <div className="space-y-2">
                        <Label>{t('service_purchase.payment_type', { defaultValue: 'Hình thức thanh toán' })}</Label>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant={paymentType === 'prepay' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => {
                                    purchaseForm.setData('payment_type', 'prepay');
                                }}
                            >
                                {t('service_purchase.payment_prepay', { defaultValue: 'Thanh toán trả trước' })}
                            </Button>
                            {(() => {
                                if (!selectedPackage) return false;
                                const packageId = selectedPackage.id;
                                const permission = postpay_permissions[packageId];
                                // Chỉ hiển thị nếu permission === true (rõ ràng là true)
                                // Nếu undefined hoặc false => ẩn nút
                                return permission === true;
                            })() && (
                                <Button
                                    type="button"
                                    variant={paymentType === 'postpay' ? 'default' : 'outline'}
                                    disabled={wallet_balance < postpayMinBalance}
                                    size="sm"
                                    onClick={() => {
                                        purchaseForm.setData('payment_type', 'postpay');
                                        purchaseForm.setData('top_up_amount', ''); // Trả sau không thu top-up upfront
                                        setPostpayDays(7); // Reset về mặc định khi chọn postpay
                                    }}
                                >
                                    {t('service_purchase.payment_postpay', { defaultValue: 'Thanh toán trả sau' })}
                                </Button>
                            )}
                        </div>
                        {paymentType === 'postpay' && (
                            <div className="space-y-3">
                                {/* Chọn số ngày trả sau */}
                                <div className="space-y-2">
                                    <Label>{t('service_purchase.postpay_days_label', { defaultValue: 'Chọn số ngày trả sau' })}</Label>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant={postpayDays === 1 ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setPostpayDays(1)}
                                        >
                                            1 {t('service_purchase.days', { defaultValue: 'ngày' })}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={postpayDays === 3 ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setPostpayDays(3)}
                                        >
                                            3 {t('service_purchase.days', { defaultValue: 'ngày' })}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={postpayDays === 7 ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setPostpayDays(7)}
                                        >
                                            7 {t('service_purchase.days', { defaultValue: 'ngày' })}
                                        </Button>
                                    </div>
                                </div>

                                {/* Thông tin */}
                                <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg space-y-2">
                                    <div className="flex items-start gap-2">
                                        <Info className="h-4 w-4 text-blue-600 mt-0.5 shrink-0" />
                                        <div className="flex-1 space-y-1">
                                            <p className="text-sm text-blue-800 font-medium">
                                                {t('service_purchase.postpay_info_title', { defaultValue: 'Thông tin thanh toán trả sau' })}
                                            </p>
                                            <ul className="text-xs text-blue-700 space-y-1 list-disc list-inside">
                                                <li>
                                                    {t('service_purchase.postpay_info_1', {
                                                        defaultValue: 'Phí dịch vụ sẽ được tính dựa trên chi tiêu thực tế hàng tháng',
                                                    })}
                                                </li>
                                                <li>
                                                    {t('service_purchase.postpay_info_2', {
                                                        defaultValue: 'Ngày thanh toán dự kiến: {{date}} (sau {{days}} ngày kể từ ngày tạo)',
                                                        date: new Date(Date.now() + postpayDays * 24 * 60 * 60 * 1000).toLocaleDateString('vi-VN', {
                                                            year: 'numeric',
                                                            month: 'long',
                                                            day: 'numeric',
                                                        }),
                                                        days: postpayDays,
                                                    })}
                                                </li>
                                                <li>
                                                    {t('service_purchase.postpay_info_3', {
                                                        defaultValue: 'Hệ thống sẽ tự động trừ tiền từ ví vào ngày đến hạn',
                                                    })}
                                                </li>
                                                <li>
                                                    {t('service_purchase.postpay_info_4', {
                                                        defaultValue: 'Nếu số dư không đủ, hệ thống sẽ tự động tạm dừng các chiến dịch quảng cáo',
                                                    })}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                        {paymentType === 'postpay' && wallet_balance < postpayMinBalance && (
                            <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <div className="flex items-center gap-2 text-amber-800">
                                    <AlertTriangle className="h-4 w-4" />
                                    <span className="text-sm font-medium">
                                        {t('service_purchase.postpay_min_wallet_warning', {
                                            defaultValue: 'Ví của bạn cần tối thiểu {{amount}} USDT để chọn thanh toán trả sau',
                                            amount: postpayMinBalance,
                                        })}
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* 
                    <div className="space-y-2">
                        <Label htmlFor="budget">
                            {t('service_purchase.budget')} ({t('service_purchase.optional', { defaultValue: 'Tùy chọn' })})
                        </Label>
                        <Input
                            id="budget"
                            type="number"
                            placeholder="0 (Unlimited)"
                            value={budgetValue}
                            onChange={(e) => {
                                purchaseForm.setData('budget', e.target.value);
                                if (purchaseForm.errors.budget) {
                                    purchaseForm.clearErrors('budget');
                                }
                            }}
                            onBlur={() => setTouchedFields(prev => ({ ...prev, budget: true }))}
                            step="0.01"
                            min="0"
                        />
                        {purchaseForm.errors.budget && (
                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                <AlertTriangle className="h-4 w-4" />
                                {purchaseForm.errors.budget}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {t('service_purchase.budget_hint_unlimited', {
                                defaultValue: 'Để trống hoặc nhập 0 để không giới hạn ngân sách'
                            })}
                        </p>
                    </div>
                    */}

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
                                onChange={(e) => {
                                    purchaseForm.setData('top_up_amount', e.target.value);
                                    if (purchaseForm.errors.top_up_amount) {
                                        purchaseForm.clearErrors('top_up_amount');
                                    }
                                }}
                                step="1"
                                disabled={paymentType === 'postpay'}
                            />
                            <Button
                                variant="outline"
                                onClick={() => setShowCalculator(!showCalculator)}
                                disabled={paymentType === 'postpay'}
                            >
                                <Calculator className="h-4 w-4" />
                            </Button>
                        </div>
                        {paymentType === 'prepay' && topUpError && (
                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                <AlertTriangle className="h-4 w-4" />
                                {topUpError}
                            </div>
                        )}
                        {purchaseForm.errors.top_up_amount && (
                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                <AlertTriangle className="h-4 w-4" />
                                {purchaseForm.errors.top_up_amount}
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
                    {(showCalculator || topUpAmount || paymentType === 'postpay') && (
                        <div className="p-4 bg-orange-50 rounded-lg space-y-3">
                            <div className="font-medium text-[#4285f4]">{t('service_purchase.calculate_fee')}:</div>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="flex justify-between">
                                        <span>{t('service_purchase.account_opening_fee')}:</span>
                                    <span className="font-medium">{formatUSDT(chargeOpenFee)}</span>
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
                            {paymentType === 'postpay' && monthlyFeePercent && (
                                <div className="flex justify-between text-sm">
                                    <span>
                                        {t('service_purchase.monthly_fee_estimate', {
                                            defaultValue: 'Ước tính phí tháng (trả sau)',
                                        })}{' '}
                                        ({monthlyFeePercent}%):
                                    </span>
                                    <span className="font-medium">{formatUSDT(monthlyFee)}</span>
                                </div>
                            )}
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
                        disabled={
                            hasInsufficientBalance ||
                            !!topUpError ||
                            purchaseForm.processing
                        }
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
                            <Info className="h-5 w-5 text-[#4285f4]" />
                            <span className="text-gray-600">{t('service_purchase.info_message')}</span>
                        </div>

                        {/* Filter and Search */}
                        <div className="flex flex-col sm:flex-row gap-4">
                            {/* Platform Filter */}
                            <div className="sm:w-48 w-full">
                                <Select value={platformFilter} onValueChange={setPlatformFilter}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('service_purchase.filter_platform', { defaultValue: 'Lọc theo nền tảng' })} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            {t('service_purchase.filter_all', { defaultValue: 'Tất cả' })}
                                        </SelectItem>
                                        <SelectItem value={String(_PlatformType.GOOGLE)}>
                                            {t('enum.platform_type.google', { defaultValue: 'Google Ads' })}
                                        </SelectItem>
                                        <SelectItem value={String(_PlatformType.META)}>
                                            {t('enum.platform_type.meta', { defaultValue: 'Meta Ads' })}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {/* Search */}
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder={t('service_purchase.search_placeholder')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
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

