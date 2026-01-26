import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE, useFormEditSupplier } from '@/pages/supplier/hooks/use-form';
import { SupplierItem } from '@/pages/supplier/types/type';
import { suppliers_index } from '@/routes';
import { useTranslation } from 'react-i18next';
import { Plus, RotateCcw, Trash2 } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';

type Props = {
    supplier: SupplierItem;
};

const Edit = ({ supplier }: Props) => {
    const { t } = useTranslation();
    const { form, submit } = useFormEditSupplier(supplier.id, supplier);
    const { data, setData, processing, errors } = form;
    const monthlySpendingError = Object.entries(errors).find(([key]) =>
        key.startsWith('monthly_spending_fee_structure'),
    )?.[1];

    const handleMonthlySpendingChange = (
        index: number,
        field: 'range' | 'fee_percent',
        value: string,
    ) => {
        const newStructure = [...data.monthly_spending_fee_structure];
        newStructure[index] = {
            ...newStructure[index],
            [field]: value,
        };
        setData('monthly_spending_fee_structure', newStructure);
    };

    const handleAddMonthlySpendingRow = () => {
        setData('monthly_spending_fee_structure', [
            ...data.monthly_spending_fee_structure,
            { range: '', fee_percent: '' },
        ]);
    };

    const handleRemoveMonthlySpendingRow = (index: number) => {
        const newStructure = data.monthly_spending_fee_structure.filter(
            (_, i) => i !== index,
        );
        setData('monthly_spending_fee_structure', newStructure);
    };

    const handleResetMonthlyTemplate = () => {
        setData('monthly_spending_fee_structure', DEFAULT_MONTHLY_SPENDING_FEE_STRUCTURE);
    };

    return (
        <AppLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {t('supplier.edit_title', { defaultValue: 'Chỉnh sửa nhà cung cấp' })}
                    </h1>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {/* Name */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <Label>{t('supplier.name', { defaultValue: 'Tên nhà cung cấp' })}</Label>
                            <Input
                                value={data.name}
                                placeholder={t('supplier.name_placeholder', { defaultValue: 'Nhập tên nhà cung cấp' })}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            {errors.name && (
                                <span className="text-sm text-red-500">
                                    {errors.name}
                                </span>
                            )}
                        </div>

                        {/* Open fee */}
                        <div className="flex flex-col gap-2">
                            <Label>{t('supplier.open_fee', { defaultValue: 'Chi phí mở tài khoản' })}</Label>
                            <Input
                                value={data.open_fee}
                                placeholder={t('supplier.open_fee_placeholder', { defaultValue: '0' })}
                                type="number"
                                step="any"
                                min="0"
                                onChange={(e) => {
                                    setData('open_fee', e.target.value);
                                }}
                                required
                            />
                            <span className="text-sm text-slate-400">
                                {t('supplier.open_fee_desc', { 
                                    defaultValue: 'Chi phí mở tài khoản của nhà cung cấp. Áp dụng cho cả trả trước và trả sau. Nếu nhập 0 thì không tính phí mở tài khoản.' 
                                })}
                            </span>
                            {errors.open_fee && (
                                <span className="text-sm text-red-500">
                                    {errors.open_fee}
                                </span>
                            )}
                        </div>

                        {/* Supplier fee percent */}
                        <div className="flex flex-col gap-2">
                            <Label>{t('supplier.supplier_fee_percent', { defaultValue: 'Chi phí nhà cung cấp (%)' })}</Label>
                            <Input
                                value={data.supplier_fee_percent || '0'}
                                placeholder={t('supplier.supplier_fee_percent_placeholder', { defaultValue: '0' })}
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                onChange={(e) => {
                                    setData('supplier_fee_percent', e.target.value);
                                }}
                            />
                            <span className="text-sm text-slate-400">
                                {t('supplier.supplier_fee_percent_desc', { 
                                    defaultValue: 'Chi phí nhà cung cấp (%) trên số tiền nạp. Ví dụ: 8%' 
                                })}
                            </span>
                            {errors.supplier_fee_percent && (
                                <span className="text-sm text-red-500">
                                    {errors.supplier_fee_percent}
                                </span>
                            )}
                        </div>

                        {/* Monthly spending & fee structure */}
                        <div className="md:col-span-2 space-y-3 rounded-lg border p-4">
                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p className="font-medium">
                                        {t('supplier.monthly_spending_title', { defaultValue: 'Monthly Spending & Fee Structure' })}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t('supplier.monthly_spending_description', { defaultValue: 'Điền biểu phí theo mức chi tiêu cho phần trả sau' })}
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
                                        {t('supplier.monthly_spending_add_row', { defaultValue: 'Thêm dòng' })}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onClick={handleResetMonthlyTemplate}
                                    >
                                        <RotateCcw className="mr-2 h-4 w-4" />
                                        {t('supplier.monthly_spending_reset_template', { defaultValue: 'Sử dụng mẫu mặc định' })}
                                    </Button>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-3">
                                <div className="hidden md:grid md:grid-cols-[1fr_1fr_auto] md:gap-3">
                                    <Label className="text-muted-foreground">
                                        {t('supplier.monthly_spending_range_label', { defaultValue: 'Monthly Spending' })}
                                    </Label>
                                    <Label className="text-muted-foreground">
                                        {t('supplier.monthly_spending_fee_label', { defaultValue: 'Fee %' })}
                                    </Label>
                                    <div></div>
                                </div>
                                {data.monthly_spending_fee_structure.map((tier, index) => {
                                    return (
                                        <div
                                            key={index}
                                            className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto]"
                                        >
                                            <Input
                                                placeholder={t('supplier.monthly_spending_range_placeholder', { defaultValue: 'Ví dụ: $10,000 – $50,000' })}
                                                value={tier.range}
                                                onChange={(e) =>
                                                    handleMonthlySpendingChange(
                                                        index,
                                                        'range',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <Input
                                                placeholder={t('supplier.monthly_spending_fee_placeholder', { defaultValue: 'Ví dụ: 5%' })}
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

                        {/* Disabled */}
                        <div className="flex flex-col gap-2 md:col-span-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="disabled"
                                    checked={data.disabled}
                                    onCheckedChange={(checked) => {
                                        setData('disabled', checked === true);
                                    }}
                                />
                                <Label
                                    htmlFor="disabled"
                                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    {t('supplier.disabled', { defaultValue: 'Khóa nhà cung cấp' })}
                                </Label>
                            </div>
                            <span className="text-sm text-slate-400">
                                {t('supplier.disabled_desc', { defaultValue: 'Nếu khóa nhà cung cấp, không thể chọn trong form tạo/sửa gói dịch vụ' })}
                            </span>
                            {errors.disabled && (
                                <span className="text-sm text-red-500">
                                    {errors.disabled}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                window.location.href = suppliers_index().url;
                            }}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                                : t('common.update', { defaultValue: 'Cập nhật' })}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
};

export default Edit;

