import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, ArrowRightLeft } from 'lucide-react';
import { useTransferForm } from '../hooks/use-transfer-form';
import { useMemo } from 'react';
import type { TransferFormProps } from '../types/type';
import { TRANSFER_PLATFORM_META, TRANSFER_PLATFORM_GOOGLE } from '../types/constants';

export const TransferForm = ({ accounts }: TransferFormProps) => {
    const { t } = useTranslation();
    const { form, handleSubmit } = useTransferForm();

    // Filter accounts by selected platform
    const filteredAccounts = useMemo(() => {
        if (!form.data.platform) {
            return [];
        }
        const platformNum = parseInt(form.data.platform);
        return accounts.filter(acc => acc.platform === platformNum);
    }, [accounts, form.data.platform]);

    // Reset account selections when platform changes
    const handlePlatformChange = (value: string) => {
        form.setData({
            ...form.data,
            platform: value,
            from_account_id: '',
            to_account_id: '',
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.transfer.create_request', { defaultValue: 'Tạo yêu cầu' })}</CardTitle>
            </CardHeader>
            <CardContent>
                <Alert className="mb-4">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                        {t('ticket.transfer.warning', {
                            defaultValue: 'Các TKQC chuyển đi cần được tắt camp tối thiểu 5 tiếng'
                        })}
                    </AlertDescription>
                </Alert>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Platform Selection */}
                    <div className="space-y-2">
                        <Label htmlFor="platform">
                            {t('ticket.transfer.platform', { defaultValue: 'Kênh quảng cáo' })}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={form.data.platform}
                            onValueChange={handlePlatformChange}
                        >
                            <SelectTrigger id="platform">
                                <SelectValue placeholder={t('ticket.transfer.select_platform', { defaultValue: 'Chọn kênh quảng cáo' })} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={String(TRANSFER_PLATFORM_META)}>
                                    {t('enum.platform_type.meta', { defaultValue: 'Meta Ads' })}
                                </SelectItem>
                                <SelectItem value={String(TRANSFER_PLATFORM_GOOGLE)}>
                                    {t('enum.platform_type.google', { defaultValue: 'Google Ads' })}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {form.errors.platform && (
                            <p className="text-sm text-red-500">{form.errors.platform}</p>
                        )}
                    </div>

                    {/* Account Selection - Only show when platform is selected */}
                    {form.data.platform && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="from_account_id">
                                    {t('ticket.transfer.from_account', { defaultValue: 'Từ tài khoản' })}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={form.data.from_account_id}
                                    onValueChange={(value) => form.setData('from_account_id', value)}
                                    disabled={!form.data.platform || filteredAccounts.length === 0}
                                >
                                    <SelectTrigger id="from_account_id">
                                        <SelectValue placeholder={t('ticket.transfer.select_account', { defaultValue: 'Chọn tài khoản' })} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredAccounts.map((account) => (
                                            <SelectItem key={account.id} value={account.account_id}>
                                                {account.account_name} ({account.account_id})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.from_account_id && (
                                    <p className="text-sm text-red-500">{form.errors.from_account_id}</p>
                                )}
                                {form.data.platform && filteredAccounts.length === 0 && (
                                    <p className="text-sm text-yellow-600">
                                        {t('ticket.transfer.no_accounts', { defaultValue: 'Không có tài khoản nào cho kênh quảng cáo này' })}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="to_account_id">
                                    {t('ticket.transfer.to_account', { defaultValue: 'Đến tài khoản' })}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={form.data.to_account_id}
                                    onValueChange={(value) => form.setData('to_account_id', value)}
                                    disabled={!form.data.platform || filteredAccounts.length === 0}
                                >
                                    <SelectTrigger id="to_account_id">
                                        <SelectValue placeholder={t('ticket.transfer.select_account', { defaultValue: 'Chọn tài khoản' })} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredAccounts.map((account) => (
                                            <SelectItem key={account.id} value={account.account_id}>
                                                {account.account_name} ({account.account_id})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.to_account_id && (
                                    <p className="text-sm text-red-500">{form.errors.to_account_id}</p>
                                )}
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="amount">
                                {t('ticket.transfer.amount', { defaultValue: 'Nhập số tiền' })} (USD)
                                <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                placeholder="0.00"
                            />
                            {form.errors.amount && (
                                <p className="text-sm text-red-500">{form.errors.amount}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('ticket.transfer.notes', { defaultValue: 'Ghi chú' })}
                        </Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder={t('ticket.transfer.notes_placeholder', { defaultValue: 'Nhập ghi chú (nếu có)' })}
                            rows={4}
                        />
                        {form.errors.notes && (
                            <p className="text-sm text-red-500">{form.errors.notes}</p>
                        )}
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            <ArrowRightLeft className="mr-2 h-4 w-4" />
                            {form.processing
                                ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                                : t('ticket.transfer.send_request', { defaultValue: 'Gửi yêu cầu' })}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

