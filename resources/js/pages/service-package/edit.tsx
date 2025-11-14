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
import { useFormEditServicePackage } from '@/pages/service-package/hooks/use-form';
import { ServicePackageItem, ServicePackageOption } from '@/pages/service-package/types/type';
import { service_packages_index } from '@/routes';
import { ReactNode, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    meta_features: ServicePackageOption[];
    google_features: ServicePackageOption[];
    service_package: ServicePackageItem;
    timezone_ids: {[key:number] : string};

};
const Edit = ({ meta_features, google_features, service_package, timezone_ids }: Props) => {
    const { t } = useTranslation();
    const { form, submit } = useFormEditServicePackage(service_package.id, service_package);

    const { data, setData, processing, errors } = form;

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
                <Label className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 has-[[aria-checked=true]]:border-red-600 has-[[aria-checked=true]]:bg-red-50">
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
                                <Label className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 has-[[aria-checked=true]]:border-blue-600 has-[[aria-checked=true]]:bg-blue-50">
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
                                    <Select
                                        value={(getCurrentFeatureValue(
                                            feature.key,
                                            0,
                                        ) as number).toString() ?? ''}
                                        onValueChange={(value) => {
                                            const numericValue = Number(value);
                                            handleFeatureChange(
                                                feature.key,
                                                numericValue
                                            )
                                        }}
                                        required
                                    >
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder={feature.label}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                {Object.entries(timezone_ids).map(([id, name]) => (
                                                    <SelectItem
                                                        key={id}
                                                        value={id}
                                                    >
                                                        {name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
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
