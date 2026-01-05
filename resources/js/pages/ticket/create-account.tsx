import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import FacebookIcon from '@/images/facebook_icon.png';
import GoogleIcon from '@/images/google_icon.png';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import { AccountForm } from '@/pages/service-purchase/components/AccountForm';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { useCreateAccountForm } from '@/pages/ticket/create-account/hooks/use-create-account-form';
import type { CreateAccountPageProps, ServicePackage } from '@/pages/ticket/create-account/types/type';
import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle,
    Plus,
    Search,
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCallback, useMemo, useState } from 'react';
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

export default function CreateAccountPage({ packages, meta_timezones = [], google_timezones = [] }: CreateAccountPageProps) {
    const { t } = useTranslation();
    const [selectedPackage, setSelectedPackage] = useState<ServicePackage | null>(null);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [platformFilter, setPlatformFilter] = useState<string>('all');
    const [touchedFields, setTouchedFields] = useState<{ budget?: boolean }>({});
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
    const [notes, setNotes] = useState<string>('');

    const packageList = useMemo(() => {
        if (Array.isArray(packages)) {
            return packages;
        }
        if (packages && Array.isArray((packages as { data?: ServicePackage[] }).data)) {
            return (packages as { data?: ServicePackage[] }).data || [];
        }
        return [];
    }, [packages]);

    const { form, submit } = useCreateAccountForm();

    const budgetValue = form.data.budget || '';

    const getInitialAccount = useCallback((platform?: number): AccountFormData => ({
        meta_email: '',
        display_name: '',
        bm_ids: [],
        fanpages: platform === _PlatformType.META ? [] : [],
        websites: [],
        timezone_bm: '',
        asset_access: 'full_asset',
    }), []);

    const handlePackageSelect = useCallback((pkg: ServicePackage) => {
        const currentPlatform = selectedPackage?.platform;
        const newPlatform = pkg.platform;

        // Nếu platform thay đổi, reset accounts
        if (currentPlatform !== newPlatform) {
            setAccounts([getInitialAccount(newPlatform)]);
        }

        setSelectedPackage(pkg);
    }, [selectedPackage?.platform, getInitialAccount]);

    const handleAccountUpdate = useCallback((index: number, updater: AccountFormData | ((prev: AccountFormData) => AccountFormData)) => {
        setAccounts((prevAccounts) => {
            const newAccounts = [...prevAccounts];
            const currentAccount = newAccounts[index] || getInitialAccount(selectedPackage?.platform);
            if (typeof updater === 'function') {
                newAccounts[index] = updater(currentAccount);
            } else {
                newAccounts[index] = updater;
            }
            return newAccounts;
        });
    }, [getInitialAccount, selectedPackage?.platform]);

    const handleAccountRemove = useCallback((index: number) => {
        setAccounts((prevAccounts) => prevAccounts.filter((_, i) => i !== index));
    }, []);

    const filteredPackages = useMemo(() => {
        let filtered: ServicePackage[] = Array.isArray(packageList) ? packageList : [];

        if (platformFilter !== 'all') {
            const platformNum = parseInt(platformFilter, 10);
            if (!isNaN(platformNum)) {
                filtered = filtered.filter((pkg) => pkg.platform === platformNum);
            }
        }

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(
                (pkg) =>
                    pkg.name?.toLowerCase().includes(query) ||
                    pkg.description?.toLowerCase().includes(query)
            );
        }

        return filtered;
    }, [packageList, platformFilter, searchQuery]);

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

    const handleSubmit = () => {
        if (!selectedPackage) return;

        setTouchedFields({ budget: true });

        if (budgetValue && validateBudget(budgetValue)) {
            alert(validateBudget(budgetValue));
            return;
        }

        const sanitizedBudget = normalizeCurrencyInput(budgetValue);
        const payloadBudget = sanitizedBudget ? sanitizedBudget : '0';

        const hasAccounts = accounts.some(
            acc => acc.meta_email || acc.display_name || (acc.bm_ids && acc.bm_ids.length > 0)
        );

        submit(
            selectedPackage.id,
            payloadBudget,
            hasAccounts ? accounts : undefined,
            undefined,
            notes || undefined,
            () => {
                setSelectedPackage(null);
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
                setNotes('');
            }
        );
    };

    const renderOrderForm = () => {
        if (!selectedPackage) return null;

        const isMeta = selectedPackage.platform === _PlatformType.META;

        return (
            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        {isMeta && (
                            <img src={FacebookIcon} alt="Facebook" className="h-6 w-6" />
                        )}
                        {!isMeta && (
                            <img src={GoogleIcon} alt="Google" className="h-6 w-6" />
                        )}
                        {isMeta
                            ? t('service_purchase.meta_account_info', { defaultValue: 'Thông tin tài khoản Meta' })
                            : t('service_purchase.google_account_info', { defaultValue: 'Thông tin tài khoản Google' })}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center justify-between">
                        <Label className="text-base font-semibold">
                            {t('service_purchase.account_info', { defaultValue: 'Thông tin tài khoản' })}
                        </Label>
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
                                            fanpages: isMeta ? [] : [],
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

                    <div className="space-y-4">
                        {accounts.map((account, idx) => (
                            <AccountForm
                                key={`account-${idx}`}
                                account={account}
                                accountIndex={idx}
                                platform={selectedPackage.platform}
                                metaTimezones={meta_timezones}
                                googleTimezones={google_timezones}
                                onUpdate={handleAccountUpdate}
                                onRemove={handleAccountRemove}
                                canRemove={accounts.length > 1}
                            />
                        ))}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="budget">{t('service_purchase.budget', { defaultValue: 'Ngân sách' })}</Label>
                        <Input
                            id="budget"
                            type="text"
                            placeholder="0"
                            value={budgetValue}
                            onChange={(e) => {
                                form.setData('budget', e.target.value);
                            }}
                            onBlur={() => setTouchedFields({ ...touchedFields, budget: true })}
                        />
                        {touchedFields.budget && budgetValue && validateBudget(budgetValue) && (
                            <p className="text-xs text-red-500">{validateBudget(budgetValue)}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {t('service_purchase.budget_description', { defaultValue: 'Ngân sách tối thiểu: 50 USD' })}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">{t('ticket.create_account.notes', { defaultValue: 'Ghi chú' })}</Label>
                        <Textarea
                            id="notes"
                            placeholder={t('ticket.create_account.notes_placeholder', { defaultValue: 'Nhập ghi chú (nếu có)' })}
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={4}
                        />
                    </div>

                    <Button
                        type="button"
                        onClick={handleSubmit}
                        disabled={form.processing}
                        className="w-full"
                    >
                        {form.processing
                            ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                            : t('ticket.create_account.submit', { defaultValue: 'Gửi yêu cầu' })}
                    </Button>
                </CardContent>
            </Card>
        );
    };

    return (
        <AppLayout>
            <Head title={t('ticket.create_account.title', { defaultValue: 'Tạo tài khoản' })} />
            <div className="container mx-auto py-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">
                        {t('ticket.create_account.title', { defaultValue: 'Tạo tài khoản' })}
                    </h1>
                    <p className="text-muted-foreground mt-2">
                        {t('ticket.create_account.description', { defaultValue: 'Điền thông tin để tạo yêu cầu tạo tài khoản mới' })}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('service_purchase.select_package', { defaultValue: 'Chọn gói dịch vụ' })}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                    <Input
                                        type="text"
                                        placeholder={t('service_purchase.search_package', { defaultValue: 'Tìm kiếm gói dịch vụ...' })}
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <Select value={platformFilter} onValueChange={setPlatformFilter}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder={t('service_purchase.filter_platform', { defaultValue: 'Lọc theo nền tảng' })} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('common.all', { defaultValue: 'Tất cả' })}</SelectItem>
                                    <SelectItem value={_PlatformType.META.toString()}>
                                        {t('platform.meta', { defaultValue: 'Meta' })}
                                    </SelectItem>
                                    <SelectItem value={_PlatformType.GOOGLE.toString()}>
                                        {t('platform.google', { defaultValue: 'Google' })}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {filteredPackages.map((pkg) => {
                                const isSelected = selectedPackage?.id === pkg.id;
                                const isMeta = pkg.platform === _PlatformType.META;
                                const isGoogle = pkg.platform === _PlatformType.GOOGLE;

                                return (
                                    <Card
                                        key={pkg.id}
                                        className={`cursor-pointer transition-all ${
                                            isSelected ? 'ring-2 ring-primary' : 'hover:shadow-md'
                                        }`}
                                        onClick={() => handlePackageSelect(pkg)}
                                    >
                                        <CardContent className="p-4">
                                            <div className="flex items-start justify-between mb-2">
                                                <div className="flex items-center gap-2">
                                                    {isMeta && (
                                                        <img src={FacebookIcon} alt="Facebook" className="h-5 w-5" />
                                                    )}
                                                    {isGoogle && (
                                                        <img src={GoogleIcon} alt="Google" className="h-5 w-5" />
                                                    )}
                                                    <h3 className="font-semibold">{pkg.name}</h3>
                                                </div>
                                                {isSelected && (
                                                    <CheckCircle className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                                {pkg.description}
                                            </p>
                                            <div className="space-y-1 text-sm">
                                                <div className="flex justify-between">
                                                    <span className="text-muted-foreground">
                                                        {t('service_purchase.open_fee', { defaultValue: 'Phí mở tài khoản' })}:
                                                    </span>
                                                    <span className="font-medium">{parseFloat(pkg.open_fee || '0').toLocaleString()} USD</span>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        {filteredPackages.length === 0 && (
                            <div className="text-center py-8 text-muted-foreground">
                                {t('service_purchase.no_package_found', { defaultValue: 'Không tìm thấy gói dịch vụ nào' })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {renderOrderForm()}

                {form.errors && Object.keys(form.errors).length > 0 && (
                    <Card className="border-red-200 bg-red-50">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-2">
                                <AlertTriangle className="h-5 w-5 text-red-600 mt-0.5" />
                                <div className="flex-1">
                                    <h4 className="font-semibold text-red-900 mb-2">
                                        {t('common.error', { defaultValue: 'Có lỗi xảy ra' })}
                                    </h4>
                                    <ul className="list-disc list-inside space-y-1 text-sm text-red-700">
                                        {Object.entries(form.errors).map(([key, value]) => (
                                            <li key={key}>{String(value)}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

