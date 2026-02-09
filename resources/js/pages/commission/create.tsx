import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { useFormCreateCommission } from '@/pages/commission/hooks/use-form';
import { commissions_index } from '@/routes';
import { useTranslation } from 'react-i18next';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type ServicePackage = {
    id: string;
    name: string;
    platform: number;
};

type Props = {
    packages: ServicePackage[];
};

const Create = ({ packages }: Props) => {
    const { t } = useTranslation();
    const { form, submit } = useFormCreateCommission();
    const { data, setData, processing, errors } = form;

    return (
        <AppLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {t('commission.create_title', { defaultValue: 'Tạo cấu hình hoa hồng' })}
                    </h1>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {/* Service Package */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <Label>
                                {t('commission.service_package', { defaultValue: 'Gói dịch vụ' })}
                            </Label>
                            <Select
                                value={data.service_package_id}
                                onValueChange={(value) => setData('service_package_id', value)}
                                required
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={t('commission.service_package_placeholder', {
                                            defaultValue: 'Chọn gói dịch vụ',
                                        })}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {packages.map((pkg) => (
                                        <SelectItem key={pkg.id} value={pkg.id}>
                                            {pkg.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.service_package_id && (
                                <span className="text-sm text-red-500">{errors.service_package_id}</span>
                            )}
                        </div>

                        {/* Type */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <Label>
                                {t('commission.type', { defaultValue: 'Loại hoa hồng' })}
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(value: 'service' | 'spending' | 'account') =>
                                    setData('type', value)
                                }
                                required
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="service">
                                        {t('commission.type_service', {
                                            defaultValue: 'Hoa hồng dịch vụ',
                                        })}
                                    </SelectItem>
                                    <SelectItem value="spending">
                                        {t('commission.type_spending', {
                                            defaultValue: 'Hoa hồng theo spending',
                                        })}
                                    </SelectItem>
                                    <SelectItem value="account">
                                        {t('commission.type_account', {
                                            defaultValue: 'Hoa hồng theo bán account',
                                        })}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <span className="text-sm text-red-500">{errors.type}</span>
                            )}
                        </div>

                        {/* Rate */}
                        <div className="flex flex-col gap-2">
                            <Label>
                                {t('commission.rate', { defaultValue: 'Tỷ lệ hoa hồng (%)' })}
                            </Label>
                            <Input
                                value={data.rate}
                                placeholder="0"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                onChange={(e) => setData('rate', e.target.value)}
                                required
                            />
                            <span className="text-sm text-slate-400">
                                {t('commission.rate_desc', {
                                    defaultValue:
                                        'Tỷ lệ hoa hồng tính theo phần trăm. Ví dụ: 5.5 = 5.5%',
                                })}
                            </span>
                            {errors.rate && (
                                <span className="text-sm text-red-500">{errors.rate}</span>
                            )}
                        </div>

                        {/* Min Amount (chỉ hiển thị khi type = spending) */}
                        {data.type === 'spending' && (
                            <div className="flex flex-col gap-2">
                                <Label>
                                    {t('commission.min_amount', {
                                        defaultValue: 'Số tiền tối thiểu (USD)',
                                    })}
                                </Label>
                                <Input
                                    value={data.min_amount || ''}
                                    placeholder="0"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    onChange={(e) => setData('min_amount', e.target.value)}
                                />
                                <span className="text-sm text-slate-400">
                                    {t('commission.min_amount_desc', {
                                        defaultValue:
                                            'Số tiền spending tối thiểu để tính hoa hồng. Để trống nếu không giới hạn.',
                                    })}
                                </span>
                                {errors.min_amount && (
                                    <span className="text-sm text-red-500">
                                        {errors.min_amount}
                                    </span>
                                )}
                            </div>
                        )}

                        {/* Max Amount (chỉ hiển thị khi type = spending) */}
                        {data.type === 'spending' && (
                            <div className="flex flex-col gap-2">
                                <Label>
                                    {t('commission.max_amount', {
                                        defaultValue: 'Số tiền tối đa (USD)',
                                    })}
                                </Label>
                                <Input
                                    value={data.max_amount || ''}
                                    placeholder="0"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    onChange={(e) => setData('max_amount', e.target.value)}
                                />
                                <span className="text-sm text-slate-400">
                                    {t('commission.max_amount_desc', {
                                        defaultValue:
                                            'Số tiền spending tối đa để tính hoa hồng. Để trống nếu không giới hạn.',
                                    })}
                                </span>
                                {errors.max_amount && (
                                    <span className="text-sm text-red-500">
                                        {errors.max_amount}
                                    </span>
                                )}
                            </div>
                        )}

                        {/* Description */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <Label>
                                {t('commission.description', { defaultValue: 'Mô tả' })}
                            </Label>
                            <Textarea
                                value={data.description || ''}
                                placeholder={t('commission.description_placeholder', {
                                    defaultValue: 'Nhập mô tả (tùy chọn)',
                                })}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                            />
                            {errors.description && (
                                <span className="text-sm text-red-500">{errors.description}</span>
                            )}
                        </div>

                        {/* Is Active */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => {
                                        setData('is_active', checked === true);
                                    }}
                                />
                                <Label
                                    htmlFor="is_active"
                                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    {t('commission.is_active', {
                                        defaultValue: 'Kích hoạt',
                                    })}
                                </Label>
                            </div>
                            <span className="text-sm text-slate-400">
                                {t('commission.is_active_desc', {
                                    defaultValue:
                                        'Chỉ tính hoa hồng khi cấu hình được kích hoạt',
                                })}
                            </span>
                            {errors.is_active && (
                                <span className="text-sm text-red-500">{errors.is_active}</span>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                window.location.href = commissions_index().url;
                            }}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                                : t('common.create', { defaultValue: 'Tạo' })}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
};

export default Create;

