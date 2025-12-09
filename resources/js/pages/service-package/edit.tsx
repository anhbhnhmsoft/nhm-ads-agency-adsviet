import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import { DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE, useFormEditServicePackage } from '@/pages/service-package/hooks/use-form';
import { ServicePackageItem, ServicePackageOption } from '@/pages/service-package/types/type';
import { service_packages_index } from '@/routes';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Plus, RotateCcw, Trash2 } from 'lucide-react';

type Props = {
    meta_features: ServicePackageOption[];
    google_features: ServicePackageOption[];
    service_package: ServicePackageItem;

};
const Edit = ({ meta_features, google_features, service_package }: Props) => {
    const { t } = useTranslation();
    const { form, submit } = useFormEditServicePackage(service_package.id, service_package);

    const { data, setData, processing, errors } = form;
    const monthlySpendingError = Object.entries(errors).find(([key]) =>
        key.startsWith('monthly_spending_fee_structure'),
    )?.[1];
    const isPlatformEditable = false;

    /**
     * Lấy danh sách Features dựa trên Platform
     * @returns Mảng Features phù hợp với Platform
     */
    const availableFeatures = useMemo(() => {
        const platformValue = Number(data.platform);
        if (platformValue === _PlatformType.META) {
            return meta_features;
        }
        if (platformValue === _PlatformType.GOOGLE) {
            return google_features;
        }
        return [];
    }, [data.platform, google_features, meta_features]);

    /**
     * Xử lý thay đổi giá trị của một Feature cụ thể
     * @param key - Key của Feature cần thay đổi
     * @param value - Giá trị mới cho Feature (boolean, number, hoặc null)
     */
    const handleFeatureChange = (
        key: string,
        value: boolean | number | null,
    ) => {
        // Tạo một bản sao mới của mảng features
        const newFeatures = [...data.features];
        // Tìm index của feature cần cập nhật
        const existingIndex = newFeatures.findIndex((f) => f.key === key);
        if (existingIndex > -1) {
            // Nếu đã tồn tại, cập nhật giá trị
            newFeatures[existingIndex].value = value;
        } else {
            // Nếu chưa tồn tại (chưa có giá trị nào được lưu), thêm mới
            newFeatures.push({ key, value });
        }
        // Cập nhật lại toàn bộ mảng features vào state của form
        setData('features', newFeatures);
    };

    /**
     * Lấy giá trị hiện tại của một Feature cụ thể
     * @param key - Key của Feature cần lấy giá trị
     * @param defaultValue - Giá trị mặc định nếu Feature không tồn tại
     * @returns Giá trị hiện tại của Feature, hoặc defaultValue nếu không có
     */
    const getCurrentFeatureValue = (
        key: string,
        defaultValue: boolean | number,
    ) => {
        const feature = data.features.find((f) => f.key === key);
        // Trả về giá trị đã lưu, hoặc null/false/0 mặc định
        return feature ? feature.value : defaultValue;
    };

    const handleMonthlySpendingChange = (
        index: number,
        field: 'range' | 'fee_percent',
        value: string,
    ) => {
        const next = [...data.monthly_spending_fee_structure];
        next[index] = {
            ...next[index],
            [field]: value,
        };
        setData('monthly_spending_fee_structure', next);
    };

    const parseMonthlyRangeToMinMax = (range: string): { min: string; max: string } => {
        const cleaned = (range || '').replace(/\$/g, '').replace(/,/g, '').trim();
        if (!cleaned) return { min: '', max: '' };

        const parts = cleaned.split(/[-–]/);
        if (parts.length >= 2) {
            return {
                min: parts[0].trim().replace(/[^\d.]/g, ''),
                max: parts[1].trim().replace(/[^\d.]/g, ''),
            };
        }

        if (cleaned.endsWith('+')) {
            return {
                min: cleaned.slice(0, -1).trim().replace(/[^\d.]/g, ''),
                max: '',
            };
        }

        return {
            min: cleaned.replace(/[^\d.]/g, ''),
            max: '',
        };
    };

    const buildMonthlyRange = (min: string, max: string): string => {
        const cleanMin = min.trim();
        const cleanMax = max.trim();
        if (cleanMin && cleanMax) return `${cleanMin}-${cleanMax}`;
        if (cleanMin && !cleanMax) return `${cleanMin}+`;
        return '';
    };

    const handleMonthlyMinChange = (index: number, min: string) => {
        const current = data.monthly_spending_fee_structure[index];
        const { max } = parseMonthlyRangeToMinMax(current?.range || '');
        const newRange = buildMonthlyRange(min, max);
        handleMonthlySpendingChange(index, 'range', newRange);
    };

    const handleMonthlyMaxChange = (index: number, max: string) => {
        const current = data.monthly_spending_fee_structure[index];
        const { min } = parseMonthlyRangeToMinMax(current?.range || '');
        const newRange = buildMonthlyRange(min, max);
        handleMonthlySpendingChange(index, 'range', newRange);
    };

    const handleAddMonthlySpendingRow = () => {
        setData('monthly_spending_fee_structure', [
            ...data.monthly_spending_fee_structure,
            { range: '', fee_percent: '' },
        ]);
    };

    const handleRemoveMonthlySpendingRow = (index: number) => {
        const next = data.monthly_spending_fee_structure.filter(
            (_, idx) => idx !== index,
        );
        setData('monthly_spending_fee_structure', next);
    };

    const handleResetMonthlyTemplate = () => {
        setData(
            'monthly_spending_fee_structure',
            DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE,
        );
    };

    return (
        <form className="space-y-4" onSubmit={submit}>
            <h1 className="text-xl font-semibold">
                {t('service_packages.title_edit')}
            </h1>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {/* Name */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.name')}</Label>
                    <Input
                        value={data.name}
                        placeholder={t('service_packages.name')}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    {errors.name && (
                        <span className="text-sm text-red-500">
                            {errors.name}
                        </span>
                    )}
                </div>

                {/* Platform */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.platform')}</Label>
                    {isPlatformEditable ? (
                        <Select
                            value={data.platform.toString()}
                            onValueChange={(value) => {
                                const numericValue = Number(value);
                                const val = numericValue as _PlatformType;
                                setData('platform', val);
                                setData('features', []);
                            }}
                            required
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder={t('service_packages.platform')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        value={_PlatformType.META.toString()}
                                    >
                                        Meta
                                    </SelectItem>
                                    <SelectItem
                                        value={_PlatformType.GOOGLE.toString()}
                                    >
                                        Google
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    ) : (
                        <Input
                            value={
                                data.platform === _PlatformType.META
                                    ? 'Meta'
                                    : 'Google'
                            }
                            disabled
                            readOnly
                        />
                    )}
                    {errors.platform && (
                        <span className="text-sm text-red-500">
                            {errors.platform}
                        </span>
                    )}
                </div>

                {/* Description */}
                <div className="flex flex-col gap-2 md:col-span-2">
                    <Label>{t('service_packages.description')}</Label>
                    <Textarea
                        value={data.description || ''}
                        placeholder={t('service_packages.description')}
                        onChange={(e) => setData('description', e.target.value)}
                        required
                    />
                    {errors.description && (
                        <span className="text-sm text-red-500">
                            {errors.description}
                        </span>
                    )}
                </div>

                {/* Monthly spending & fee structure */}
                <div className="md:col-span-2 space-y-3 rounded-lg border p-4">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="font-medium">
                                {t('service_packages.monthly_spending_title')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t(
                                    'service_packages.monthly_spending_description',
                                )}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={handleAddMonthlySpendingRow}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {t('service_packages.monthly_spending_add_row')}
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={handleResetMonthlyTemplate}
                            >
                                <RotateCcw className="mr-2 h-4 w-4" />
                                {t(
                                    'service_packages.monthly_spending_reset_template',
                                )}
                            </Button>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-3">
                        <div className="hidden md:grid md:grid-cols-[1fr_1fr_1fr_auto] md:gap-3">
                            <Label className="text-muted-foreground">
                                {t('service_packages.monthly_spending_min_label', { defaultValue: 'Min' })}
                            </Label>
                            <Label className="text-muted-foreground">
                                {t('service_packages.monthly_spending_max_label', { defaultValue: 'Max' })}
                            </Label>
                            <Label className="text-muted-foreground">
                                {t('service_packages.monthly_spending_fee_label')}
                            </Label>
                            <span />
                        </div>
                        {data.monthly_spending_fee_structure.map((tier, index) => {
                            const { min, max } = parseMonthlyRangeToMinMax(tier.range);
                            return (
                                <div
                                    key={`monthly-tier-${index}`}
                                    className="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]"
                                >
                                    <Input
                                        placeholder={t('service_packages.monthly_spending_min_label', { defaultValue: 'Min' })}
                                        type="number"
                                        value={min}
                                        onChange={(e) => handleMonthlyMinChange(index, e.target.value)}
                                    />
                                    <Input
                                        placeholder={t('service_packages.monthly_spending_max_label', { defaultValue: 'Max' })}
                                        type="number"
                                        value={max}
                                        onChange={(e) => handleMonthlyMaxChange(index, e.target.value)}
                                    />
                                    <Input
                                        placeholder={t('service_packages.monthly_spending_fee_label')}
                                        value={tier.fee_percent}
                                        onChange={(e) =>
                                            handleMonthlySpendingChange(
                                                index,
                                                'fee_percent',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="justify-self-start md:justify-self-end"
                                        size="icon"
                                        onClick={() =>
                                            handleRemoveMonthlySpendingRow(
                                                index,
                                            )
                                        }
                                        disabled={
                                            data.monthly_spending_fee_structure
                                                .length === 1
                                        }
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            );
                        })}
                    </div>
                    {monthlySpendingError && (
                        <span className="text-sm text-red-500">
                            {monthlySpendingError}
                        </span>
                    )}
                </div>

                {/* Open fee */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.open_fee')}</Label>
                    <Input
                        value={data.open_fee}
                        placeholder={t('service_packages.open_fee')}
                        // Thay đổi từ "number" sang "text"
                        type="number"
                        step={'any'}
                        onChange={(e) => {
                            const stringValue = e.target.value;
                            setData('open_fee', stringValue);
                        }}
                        required
                    />
                    {errors.open_fee && (
                        <span className="text-sm text-red-500">
                            {errors.open_fee}
                        </span>
                    )}
                </div>

                {/* Range min top up */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.range_min_top_up')}</Label>
                    <Input
                        value={data.range_min_top_up}
                        placeholder={t('service_packages.range_min_top_up')}
                        // Thay đổi từ "number" sang "text"
                        type="number"
                        step={'any'}
                        onChange={(e) => {
                            const stringValue = e.target.value;
                            setData('range_min_top_up', stringValue);
                        }}
                        required
                    />
                    <span className="text-sm text-slate-400">
                        {t('service_packages.range_min_top_up_desc')}
                    </span>
                    {errors.range_min_top_up && (
                        <span className="text-sm text-red-500">
                            {errors.range_min_top_up}
                        </span>
                    )}
                </div>

                {/* Top up fee */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.top_up_fee')}</Label>
                    <Input
                        value={data.top_up_fee}
                        placeholder={t('service_packages.top_up_fee')}
                        type="number"
                        step={'any'}
                        onChange={(e) => {
                            const stringValue = e.target.value;
                            setData('top_up_fee', stringValue);
                        }}
                        required
                    />
                    {errors.top_up_fee && (
                        <span className="text-sm text-red-500">
                            {errors.top_up_fee}
                        </span>
                    )}
                </div>

                {/* Set up time */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.set_up_time')}</Label>
                    <Input
                        value={data.set_up_time}
                        placeholder={t('service_packages.set_up_time')}
                        type="number"
                        step={'any'}
                        onChange={(e) => {
                            setData('set_up_time', e.target.value);
                        }}
                        required
                    />
                    <span className="text-sm text-slate-400">
                        {t('service_packages.set_up_time_desc')}
                    </span>
                    {errors.set_up_time && (
                        <span className="text-sm text-red-500">
                            {errors.set_up_time}
                        </span>
                    )}
                </div>

                {/* Disabled */}
                <Label className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 has-aria-checked:border-red-600 has-aria-checked:bg-red-50">
                    <Checkbox
                        disabled={false}
                        checked={data.disabled}
                        onCheckedChange={(value) => {
                            return setData('disabled', value as boolean);
                        }}
                        className="data-[state=checked]:border-red-600 data-[state=checked]:bg-red-600 data-[state=checked]:text-white"
                    />
                    <div className="grid gap-1.5 font-normal">
                        <p className="text-sm leading-none font-medium">
                            {t('service_packages.disabled')}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {t('service_packages.disabled_desc')}
                        </p>
                    </div>
                </Label>
            </div>
            {/* Features */}
            <h1 className="text-xl font-semibold">
                {data.platform === _PlatformType.META
                    ? t('service_packages.meta_features')
                    : t('service_packages.google_features')}
            </h1>
            <div className={'grid grid-cols-1 gap-6 md:grid-cols-2'}>
                {availableFeatures.map((feature) => {
                    if (feature.type === 'boolean') {
                        return (
                            <div key={feature.key} className={'flex flex-col gap-2'}>
                                <Label className="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 hover:bg-accent/50 has-aria-checked:border-blue-600 has-aria-checked:bg-blue-50">
                                    <Checkbox
                                        id={feature.key}
                                        disabled={false}
                                        checked={
                                            !!getCurrentFeatureValue(
                                                feature.key,
                                                false,
                                            )
                                        }
                                        onCheckedChange={(value) => {
                                            const val = value as boolean;
                                            // e là Event, nhưng Checkbox component của shadcn trả về giá trị trực tiếp
                                            return handleFeatureChange(
                                                feature.key,
                                                val,
                                            ); // <--- Nguồn gốc vấn đề 2
                                        }}
                                        className="data-[state=checked]:border-blue-600 data-[state=checked]:bg-blue-600 data-[state=checked]:text-white"
                                    />
                                    <div className="grid gap-1.5 font-normal">
                                        <p className="text-sm leading-none font-medium">
                                            {feature.label}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {t('service_packages.toggle_desc')}
                                        </p>
                                    </div>
                                </Label>
                            </div>
                        )
                    }

                    if (feature.type === 'number') {
                        if (feature.key === 'meta_timezone_id') {
                            return (
                                <div key={feature.key} className={'flex flex-col gap-2'}>
                                    <Label htmlFor={feature.key}>
                                        {feature.label}
                                    </Label>
                                    <Input
                                        type="number"
                                        value={
                                            (getCurrentFeatureValue(
                                                feature.key,
                                                0,
                                            ) as number) ?? ''
                                        }
                                        onChange={(e) =>
                                            handleFeatureChange(
                                                feature.key,
                                                parseFloat(e.target.value) || 0,
                                            )
                                        }
                                    />
                                    <span className="text-sm text-slate-400">
                                        {t('service_packages.meta_timezone_id_desc')}
                                    </span>
                                </div>
                            )
                        }else{
                            return (
                                <div key={feature.key} className={'flex flex-col gap-2'}>
                                    <Label htmlFor={feature.key}>
                                        {feature.label}
                                    </Label>
                                    <Input
                                        type="text" // Dùng text để hỗ trợ thập phân
                                        inputMode="decimal"
                                        value={
                                            (getCurrentFeatureValue(
                                                feature.key,
                                                0,
                                            ) as number) ?? ''
                                        }
                                        onChange={(e) =>
                                            handleFeatureChange(
                                                feature.key,
                                                parseFloat(e.target.value) || 0,
                                            )
                                        }
                                    />
                                </div>
                            )
                        }
                    }
                })}
                {errors.features && (
                    <p className="text-sm text-red-500">{errors.features}</p>
                )}
            </div>

            <Button type="submit" disabled={processing}>
                {t('common.save')}
            </Button>
        </form>
    );
};

Edit.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[
            {
                title: 'menu.service_packages',
                href: service_packages_index().url,
            },
            {
                title: 'service_packages.title_edit',
            },
        ]}
        children={page}
    />
);

export default Edit;
