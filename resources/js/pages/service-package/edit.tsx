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
import UserMultiSelect from '@/pages/service-package/components/user-multi-select';
import {
    DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE,
    useFormEditServicePackage,
} from '@/pages/service-package/hooks/use-form';
import {
    ServicePackageItem,
    ServicePackageOption,
    SupplierOption,
    UserOption,
} from '@/pages/service-package/types/type';
import { service_packages_index } from '@/routes';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertCircle,
    ArrowLeft,
    Database,
    Info,
    Layers,
    Plus,
    RotateCcw,
    Trash2,
    Upload,
    UserCheck,
} from 'lucide-react';
import { ReactNode, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    meta_features: ServicePackageOption[];
    google_features: ServicePackageOption[];
    service_package: ServicePackageItem;
    suppliers?: SupplierOption[];
    all_users?: UserOption[];
};

type InventoryItem = {
    id: string;
    account_id: string;
    account_name?: string | null;
    business_manager_id?: string | null;
    customer_manager_id?: string | null;
    status: 'available' | 'reserved' | 'assigned' | 'failed' | string;
    assigned_service_user_id?: string | null;
    link_target_type?: string | null;
    link_target_value?: string | null;
    last_error?: string | null;
};

const Edit = ({
    meta_features,
    google_features,
    service_package,
    suppliers = [],
    all_users = [],
}: Props) => {
    const { t } = useTranslation();
    const { form, submit } = useFormEditServicePackage(
        service_package.id,
        service_package,
    );

    const { data, setData, processing, errors } = form;
    const [inventoryItems, setInventoryItems] = useState<InventoryItem[]>([]);
    const [inventoryImportText, setInventoryImportText] = useState('');
    const [inventoryLoading, setInventoryLoading] = useState(false);
    const [inventorySubmitting, setInventorySubmitting] = useState(false);
    const monthlySpendingError = Object.entries(errors).find(([key]) =>
        key.startsWith('monthly_spending_fee_structure'),
    )?.[1];
    const isPlatformEditable = false;
    const inventoryUrl = `/service-packages/${service_package.id}/account-inventory`;

    const loadInventory = async () => {
        setInventoryLoading(true);
        try {
            const response = await axios.get(inventoryUrl);
            setInventoryItems(response.data?.data || []);
        } finally {
            setInventoryLoading(false);
        }
    };

    useEffect(() => {
        loadInventory();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [service_package.id]);

    const inventoryStats = useMemo(() => {
        return inventoryItems.reduce(
            (acc, item) => {
                acc.total += 1;
                acc[item.status] = (acc[item.status] || 0) + 1;
                return acc;
            },
            { total: 0 } as Record<string, number>,
        );
    }, [inventoryItems]);

    const parseInventoryImport = () => {
        return inventoryImportText
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line) => {
                const [account_id, account_name, manager_id, note] = line
                    .split(/[,\t]/)
                    .map((part) => part.trim());

                return {
                    account_id,
                    account_name: account_name || undefined,
                    business_manager_id:
                        data.platform === _PlatformType.META
                            ? manager_id || undefined
                            : undefined,
                    customer_manager_id:
                        data.platform === _PlatformType.GOOGLE
                            ? manager_id || undefined
                            : undefined,
                    note: note || undefined,
                };
            })
            .filter((item) => item.account_id);
    };

    const handleImportInventory = async () => {
        const accounts = parseInventoryImport();
        if (accounts.length === 0) return;

        setInventorySubmitting(true);
        try {
            await axios.post(`${inventoryUrl}/import`, { accounts });
            setInventoryImportText('');
            await loadInventory();
        } finally {
            setInventorySubmitting(false);
        }
    };

    const handleDeleteInventory = async (inventoryId: string) => {
        if (
            !confirm(
                t('common.confirm_delete', { defaultValue: 'Xác nhận xoá?' }),
            )
        ) {
            return;
        }

        await axios.delete(`${inventoryUrl}/${inventoryId}`);
        await loadInventory();
    };

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

    const parseMonthlyRangeToMinMax = (
        range: string,
    ): { min: string; max: string } => {
        const cleaned = (range || '')
            .replace(/\$/g, '')
            .replace(/,/g, '')
            .trim();
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
                min: cleaned
                    .slice(0, -1)
                    .trim()
                    .replace(/[^\d.]/g, ''),
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

                {/* Supplier */}
                <div className="flex flex-col gap-2">
                    <Label>
                        {t('service_packages.supplier', {
                            defaultValue: 'Nhà cung cấp',
                        })}
                    </Label>
                    <Select
                        value={data.supplier_id || undefined}
                        onValueChange={(value) => {
                            setData('supplier_id', value || null);
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue
                                placeholder={t(
                                    'service_packages.supplier_placeholder',
                                    { defaultValue: 'Chọn nhà cung cấp' },
                                )}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {suppliers.map((supplier) => (
                                <SelectItem
                                    key={supplier.id}
                                    value={supplier.id}
                                >
                                    {supplier.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.supplier_id && (
                        <span className="text-sm text-red-500">
                            {errors.supplier_id}
                        </span>
                    )}
                </div>

                {/* Payment type */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.payment_type')}</Label>
                    <Select
                        value={data.payment_type}
                        onValueChange={(value) => {
                            const paymentType = value as 'prepay' | 'postpay';
                            setData('payment_type', paymentType);
                            if (paymentType === 'postpay') {
                                setData('billing_source', 'customer_card');
                            } else if (
                                data.billing_source === 'customer_card'
                            ) {
                                setData('billing_source', 'adviet_card');
                            }
                        }}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue
                                placeholder={t('service_packages.payment_type')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="prepay">
                                    {t('service_packages.payment_type_prepay')}
                                </SelectItem>
                                <SelectItem value="postpay">
                                    {t('service_packages.payment_type_postpay')}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    {errors.payment_type && (
                        <span className="text-sm text-red-500">
                            {errors.payment_type}
                        </span>
                    )}
                </div>

                {/* Billing source */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.billing_source')}</Label>
                    <Select
                        value={data.billing_source}
                        onValueChange={(value) => {
                            setData(
                                'billing_source',
                                value as
                                    | 'customer_card'
                                    | 'adviet_card'
                                    | 'supplier_credit_line',
                            );
                        }}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue
                                placeholder={t(
                                    'service_packages.billing_source',
                                )}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="adviet_card">
                                    {t(
                                        'service_packages.billing_sources.adviet_card',
                                    )}
                                </SelectItem>
                                <SelectItem value="customer_card">
                                    {t(
                                        'service_packages.billing_sources.customer_card',
                                    )}
                                </SelectItem>
                                <SelectItem value="supplier_credit_line">
                                    {t(
                                        'service_packages.billing_sources.supplier_credit_line',
                                    )}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <span className="text-sm text-slate-400">
                        {t('service_packages.billing_source_desc')}
                    </span>
                    {errors.billing_source && (
                        <span className="text-sm text-red-500">
                            {errors.billing_source}
                        </span>
                    )}
                </div>

                <div className="flex flex-col gap-2 md:col-span-2">
                    <Label>{t('service_packages.allowed_users_label')}</Label>
                    <p className="text-sm text-muted-foreground">
                        {t('service_packages.allowed_users_description')}
                    </p>
                    <UserMultiSelect
                        users={all_users}
                        value={data.allowed_user_ids}
                        onChange={(ids) => setData('allowed_user_ids', ids)}
                    />
                    {errors.allowed_user_ids && (
                        <span className="text-sm text-red-500">
                            {errors.allowed_user_ids}
                        </span>
                    )}
                </div>

                {/* Description */}
                <div className="flex flex-col gap-2 md:col-span-2">
                    <Label>
                        {t('service_packages.description')}{' '}
                        <span className="text-red-500">*</span>
                    </Label>
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
                <div className="space-y-3 rounded-lg border p-4 md:col-span-2">
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
                                {t(
                                    'service_packages.monthly_spending_min_label',
                                    { defaultValue: 'Min' },
                                )}
                            </Label>
                            <Label className="text-muted-foreground">
                                {t(
                                    'service_packages.monthly_spending_max_label',
                                    { defaultValue: 'Max' },
                                )}
                            </Label>
                            <Label className="text-muted-foreground">
                                {t(
                                    'service_packages.monthly_spending_fee_label',
                                )}
                            </Label>
                            <span />
                        </div>
                        {data.monthly_spending_fee_structure.map(
                            (tier, index) => {
                                const { min, max } = parseMonthlyRangeToMinMax(
                                    tier.range,
                                );
                                return (
                                    <div
                                        key={`monthly-tier-${index}`}
                                        className="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]"
                                    >
                                        <Input
                                            placeholder={t(
                                                'service_packages.monthly_spending_min_label',
                                                { defaultValue: 'Min' },
                                            )}
                                            type="number"
                                            value={min}
                                            onChange={(e) =>
                                                handleMonthlyMinChange(
                                                    index,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <Input
                                            placeholder={t(
                                                'service_packages.monthly_spending_max_label',
                                                { defaultValue: 'Max' },
                                            )}
                                            type="number"
                                            value={max}
                                            onChange={(e) =>
                                                handleMonthlyMaxChange(
                                                    index,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <Input
                                            placeholder={t(
                                                'service_packages.monthly_spending_fee_label',
                                            )}
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
                                                data
                                                    .monthly_spending_fee_structure
                                                    .length === 1
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                );
                            },
                        )}
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

                {/* Spending fee */}
                <div className="flex flex-col gap-2">
                    <Label>{t('service_packages.spending_fee')}</Label>
                    <Input
                        value={data.spending_fee}
                        placeholder={t('service_packages.spending_fee')}
                        type="number"
                        step={'any'}
                        min="0"
                        max="100"
                        onChange={(e) => {
                            const stringValue = e.target.value;
                            setData('spending_fee', stringValue);
                        }}
                    />
                    <span className="text-sm text-slate-400">
                        {t('service_packages.spending_fee_desc')}
                    </span>
                    {errors.spending_fee && (
                        <span className="text-sm text-red-500">
                            {errors.spending_fee}
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

                {/* Refund open fee */}
                <div className="flex flex-col gap-2">
                    <Label className="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 hover:bg-accent/50 has-aria-checked:border-orange-600 has-aria-checked:bg-orange-50">
                        <Checkbox
                            disabled={false}
                            checked={data.refund_open_fee}
                            onCheckedChange={(value) => {
                                return setData('refund_open_fee', value as boolean);
                            }}
                            className="data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600 data-[state=checked]:text-white"
                        />
                        <div className="grid gap-1.5 font-normal">
                            <p className="text-sm leading-none font-medium">
                                {t('service_packages.refund_open_fee', { defaultValue: 'Hoàn phí mở tài khoản khi đạt ngưỡng chi tiêu' })}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('service_packages.refund_open_fee_desc', { defaultValue: 'Tự động hoàn lại phí mở tài khoản khi khách hàng đạt tổng chi tiêu yêu cầu.' })}
                            </p>
                        </div>
                    </Label>
                </div>
                {data.refund_open_fee && (
                    <div className="flex flex-col gap-2">
                        <Label>{t('service_packages.min_spend_for_refund', { defaultValue: 'Chi tiêu tối thiểu để hoàn phí ($)' })}</Label>
                        <Input
                            value={data.min_spend_for_refund || '0'}
                            placeholder={t('service_packages.min_spend_for_refund_placeholder', { defaultValue: 'Ví dụ: 10000' })}
                            type="number"
                            step="any"
                            min="0"
                            onChange={(e) => {
                                setData('min_spend_for_refund', e.target.value);
                            }}
                        />
                        {errors.min_spend_for_refund && (
                            <span className="text-sm text-red-500">
                                {errors.min_spend_for_refund}
                            </span>
                        )}
                    </div>
                )}

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

            {data.platform === _PlatformType.META ||
            data.platform === _PlatformType.GOOGLE ? (
                <div className="space-y-3 rounded-lg border p-4">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="font-medium">
                                {data.platform === _PlatformType.META
                                    ? t('service_packages.meta_features')
                                    : t('service_packages.google_features')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {data.platform === _PlatformType.META
                                    ? t('service_packages.meta_features_desc', {
                                          defaultValue:
                                              'Nhập các rule Meta Ads. Mỗi dòng là một rule với tiêu đề và mô tả.',
                                      })
                                    : t(
                                          'service_packages.google_features_desc',
                                          {
                                              defaultValue:
                                                  'Nhập các rule Google Ads. Mỗi dòng là một rule với tiêu đề và mô tả.',
                                          },
                                      )}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => {
                                const newFeatures = [
                                    ...data.features,
                                    { key: '', value: '' },
                                ];
                                setData('features', newFeatures);
                            }}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            {t('service_packages.meta_features_add_rule', {
                                defaultValue: '+ Thêm rule',
                            })}
                        </Button>
                    </div>
                    <div className="grid grid-cols-1 gap-3">
                        <div className="hidden md:grid md:grid-cols-[1fr_1fr_auto] md:gap-3">
                            <Label className="text-muted-foreground">
                                {t('service_packages.meta_features_key_label', {
                                    defaultValue: 'Tiêu đề',
                                })}
                            </Label>
                            <Label className="text-muted-foreground">
                                {t(
                                    'service_packages.meta_features_value_label',
                                    { defaultValue: 'Mô tả' },
                                )}
                            </Label>
                            <span />
                        </div>
                        {data.features.map((feature, index) => (
                            <div
                                key={`feature-${index}`}
                                className="grid gap-2 md:grid-cols-[1fr_1fr_auto]"
                            >
                                <Input
                                    placeholder={t(
                                        'service_packages.meta_features_key_placeholder',
                                        { defaultValue: 'Nhập tiêu đề' },
                                    )}
                                    value={feature.key || ''}
                                    onChange={(e) => {
                                        const newFeatures = [...data.features];
                                        newFeatures[index] = {
                                            key: e.target.value,
                                            value: newFeatures[index].value,
                                        };
                                        setData('features', newFeatures);
                                    }}
                                />
                                <Input
                                    placeholder={t(
                                        'service_packages.meta_features_value_placeholder',
                                        { defaultValue: 'Nhập mô tả' },
                                    )}
                                    value={
                                        typeof feature.value === 'string'
                                            ? feature.value
                                            : feature.value?.toString() || ''
                                    }
                                    onChange={(e) => {
                                        const newFeatures = [...data.features];
                                        // Lưu value dạng text để admin nhập tự do
                                        newFeatures[index] = {
                                            key: newFeatures[index].key,
                                            value: e.target.value,
                                        };
                                        setData('features', newFeatures);
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="justify-self-start md:justify-self-end"
                                    size="icon"
                                    onClick={() => {
                                        const newFeatures =
                                            data.features.filter(
                                                (_, idx) => idx !== index,
                                            );
                                        setData('features', newFeatures);
                                    }}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                    {errors.features && (
                        <span className="text-sm text-red-500">
                            {errors.features}
                        </span>
                    )}
                </div>
            ) : (
                <div className={'grid grid-cols-1 gap-6 md:grid-cols-2'}>
                    {availableFeatures.map((feature) => {
                        if (feature.type === 'boolean') {
                            return (
                                <div
                                    key={feature.key}
                                    className={'flex flex-col gap-2'}
                                >
                                    <Label className="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 hover:bg-accent/50 has-aria-checked:border-[#4285f4] has-aria-checked:bg-orange-50">
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
                                                return handleFeatureChange(
                                                    feature.key,
                                                    val,
                                                );
                                            }}
                                            className="data-[state=checked]:border-[#4285f4] data-[state=checked]:bg-[#4285f4] data-[state=checked]:text-white"
                                        />
                                        <div className="grid gap-1.5 font-normal">
                                            <p className="text-sm leading-none font-medium">
                                                {feature.label}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'service_packages.toggle_desc',
                                                )}
                                            </p>
                                        </div>
                                    </Label>
                                </div>
                            );
                        }

                        if (feature.type === 'number') {
                            return (
                                <div
                                    key={feature.key}
                                    className={'flex flex-col gap-2'}
                                >
                                    <Label htmlFor={feature.key}>
                                        {feature.label}
                                    </Label>
                                    <Input
                                        type="text"
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
                            );
                        }
                    })}
                    {errors.features && (
                        <p className="text-sm text-red-500">
                            {errors.features}
                        </p>
                    )}
                </div>
            )}

            <div className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                {/* Header Section */}
                <div className="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-center md:justify-between">
                    <div className="flex items-start gap-3">
                        <div className="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                            <Database className="h-6 w-6" />
                        </div>
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">
                                {t('service_packages.account_inventory_title', {
                                    defaultValue: 'Kho tài khoản bán tự động',
                                })}
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                {t(
                                    'service_packages.account_inventory_description',
                                    {
                                        defaultValue:
                                            'Khi khách hàng mua gói dịch vụ này, hệ thống sẽ tự động phân phối các tài khoản còn trống trong kho.',
                                    },
                                )}
                            </p>
                        </div>
                    </div>

                    {/* Stats Badges */}
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                            <Layers className="h-3.5 w-3.5 text-slate-500" />
                            <span>Total:</span>
                            <span className="font-bold text-slate-950">
                                {inventoryStats.total || 0}
                            </span>
                        </div>
                        <div className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-500" />
                            <span>Available:</span>
                            <span className="font-bold text-emerald-950">
                                {inventoryStats.available || 0}
                            </span>
                        </div>
                        <div className="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                            <UserCheck className="h-3.5 w-3.5 text-blue-500" />
                            <span>Assigned:</span>
                            <span className="font-bold text-blue-950">
                                {inventoryStats.assigned || 0}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Import Guide Alert */}
                <div className="rounded-lg border border-blue-100 bg-blue-50/40 p-4 text-blue-950">
                    <div className="flex gap-2.5">
                        <Info className="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />
                        <div className="space-y-1.5 text-sm">
                            <p className="font-semibold text-blue-900">
                                Hướng dẫn Import kho tài khoản
                            </p>
                            <p className="leading-relaxed text-blue-800">
                                Nhập danh sách tài khoản cần thêm vào kho, mỗi
                                tài khoản viết trên một dòng. Định dạng phân
                                cách bởi dấu phẩy{' '}
                                <code className="rounded bg-blue-100 px-1 py-0.5 font-mono font-bold text-blue-900">
                                    ,
                                </code>{' '}
                                hoặc khoảng Tab:
                            </p>
                            <div className="mt-2 rounded-md border border-blue-100/60 bg-white/80 p-2.5 font-mono text-xs text-blue-800/90 shadow-inner">
                                {data.platform === _PlatformType.META
                                    ? 'act_123456789, Tên tài khoản, 987654321 (BM ID), Ghi chú thêm'
                                    : '1234567890 (Customer ID), Tên tài khoản, 9876543210 (MCC ID), Ghi chú thêm'}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Textarea Import Section */}
                <div className="space-y-2">
                    <Label className="text-sm font-semibold text-slate-800">
                        Dữ liệu Import
                    </Label>
                    <div className="flex flex-col gap-3">
                        <Textarea
                            value={inventoryImportText}
                            onChange={(event) =>
                                setInventoryImportText(event.target.value)
                            }
                            placeholder={
                                data.platform === _PlatformType.META
                                    ? 'act_123456789, Account name, 987654321, note'
                                    : '1234567890, Account name, 9876543210, note'
                            }
                            rows={4}
                            className="border-slate-200 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500/20"
                        />
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-xs text-slate-400 italic">
                                * Lưu ý: Nhập đúng định dạng để hệ thống phân
                                tách chính xác các cột dữ liệu.
                            </span>
                            <Button
                                type="button"
                                onClick={handleImportInventory}
                                disabled={
                                    inventorySubmitting ||
                                    !inventoryImportText.trim()
                                }
                                className="flex shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white shadow-sm transition-colors hover:bg-indigo-700"
                            >
                                {inventorySubmitting ? (
                                    <>
                                        <RotateCcw className="h-4 w-4 animate-spin" />
                                        <span>
                                            {t('common.loading', {
                                                defaultValue: 'Đang tải...',
                                            })}
                                        </span>
                                    </>
                                ) : (
                                    <>
                                        <Upload className="h-4 w-4" />
                                        <span>
                                            {t(
                                                'service_packages.account_inventory_import',
                                                { defaultValue: 'Import kho' },
                                            )}
                                        </span>
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="space-y-2.5">
                    <Label className="text-sm font-semibold text-slate-800">
                        Danh sách tài khoản trong kho
                    </Label>
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-slate-50/50 shadow-inner">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[760px] text-sm">
                                <thead className="border-b border-slate-200 bg-slate-100 font-semibold text-slate-600">
                                    <tr>
                                        <th className="p-3.5 text-left font-semibold">
                                            Tài khoản (Account)
                                        </th>
                                        <th className="p-3.5 text-left font-semibold">
                                            BM/MCC ID
                                        </th>
                                        <th className="p-3.5 text-left font-semibold">
                                            Trạng thái
                                        </th>
                                        <th className="p-3.5 text-left font-semibold">
                                            Đối tượng sử dụng
                                        </th>
                                        <th className="p-3.5 text-right font-semibold">
                                            Hành động
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-slate-150 divide-y bg-white">
                                    {inventoryLoading ? (
                                        <tr>
                                            <td
                                                className="p-8 text-center text-slate-400"
                                                colSpan={5}
                                            >
                                                <div className="flex flex-col items-center justify-center gap-2">
                                                    <RotateCcw className="h-5 w-5 animate-spin text-indigo-500" />
                                                    <span>
                                                        {t('common.loading', {
                                                            defaultValue:
                                                                'Đang tải...',
                                                        })}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : inventoryItems.length === 0 ? (
                                        <tr>
                                            <td
                                                className="p-8 text-center text-slate-400"
                                                colSpan={5}
                                            >
                                                <div className="flex flex-col items-center justify-center gap-2">
                                                    <Database className="h-8 w-8 text-slate-300" />
                                                    <span>
                                                        {t(
                                                            'common.no_data_display',
                                                        )}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : (
                                        inventoryItems.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="transition-colors hover:bg-slate-50/40"
                                            >
                                                <td className="p-3.5">
                                                    <div className="font-semibold text-slate-900">
                                                        {item.account_name ||
                                                            'Chưa gán tên'}
                                                    </div>
                                                    <div className="mt-0.5 font-mono text-xs text-slate-500">
                                                        {item.account_id}
                                                    </div>
                                                    {item.last_error && (
                                                        <div className="mt-1.5 flex items-center gap-1 rounded border border-rose-100 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-600">
                                                            <AlertCircle className="h-3.5 w-3.5 shrink-0" />
                                                            <span>
                                                                {
                                                                    item.last_error
                                                                }
                                                            </span>
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-3.5">
                                                    {item.business_manager_id ||
                                                    item.customer_manager_id ? (
                                                        <span className="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">
                                                            {item.business_manager_id ||
                                                                item.customer_manager_id}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400 italic">
                                                            -
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="p-3.5">
                                                    {item.status ===
                                                    'available' ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                                            Sẵn sàng
                                                        </span>
                                                    ) : item.status ===
                                                      'assigned' ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-blue-500" />
                                                            Đã bàn giao
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                            {item.status}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="p-3.5 text-slate-700">
                                                    {item.link_target_value ? (
                                                        <div className="flex flex-col gap-0.5">
                                                            <span className="text-xs font-medium tracking-wider text-slate-500 uppercase">
                                                                {
                                                                    item.link_target_type
                                                                }
                                                            </span>
                                                            <span className="font-medium text-slate-900">
                                                                {
                                                                    item.link_target_value
                                                                }
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 italic">
                                                            Chưa bàn giao
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="p-3.5 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            handleDeleteInventory(
                                                                item.id,
                                                            )
                                                        }
                                                        disabled={
                                                            item.status ===
                                                            'assigned'
                                                        }
                                                        className="rounded-lg text-slate-400 transition-colors hover:bg-rose-50/50 hover:text-rose-600"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.visit(service_packages_index().url)}
                >
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    {t('common.back')}
                </Button>
                <Button type="submit" disabled={processing}>
                    {t('common.save')}
                </Button>
            </div>
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
