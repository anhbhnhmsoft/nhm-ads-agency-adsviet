import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { PlatformAccountSelector, type AccountOption } from '../../components/PlatformAccountSelector';
import { useRefundForm } from '../hooks/use-refund-form';
import { useState, useEffect } from 'react';
import type { RefundFormProps } from '../types/type';
import { REFUND_TYPE_WITHDRAW_TO_WALLET } from '../types/constants';

export const RefundForm = ({ accounts }: RefundFormProps) => {
    const { t } = useTranslation();
    const { form, handleSubmit } = useRefundForm();
    const [selectedAccounts, setSelectedAccounts] = useState<string[]>([]);

    useEffect(() => {
        setSelectedAccounts(form.data.account_ids || []);
    }, [form.data.account_ids]);

    const handlePlatformChange = (platform: string) => {
        form.setData({
            ...form.data,
            platform: platform,
            account_ids: [],
        });
        setSelectedAccounts([]);
    };

    const handleAddAccount = () => {
        if (form.data.account_id && !selectedAccounts.includes(form.data.account_id)) {
            const newAccounts = [...selectedAccounts, form.data.account_id];
            setSelectedAccounts(newAccounts);
            form.setData('account_ids', newAccounts);
            form.setData('account_id', '');
        }
    };

    const handleRemoveAccount = (accountId: string) => {
        const newAccounts = selectedAccounts.filter(id => id !== accountId);
        setSelectedAccounts(newAccounts);
        form.setData('account_ids', newAccounts);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.refund.create_request', { defaultValue: 'Tạo yêu cầu' })}</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <PlatformAccountSelector
                        accounts={accounts}
                        selectedPlatform={form.data.platform}
                        selectedAccountId={form.data.account_id}
                        onPlatformChange={handlePlatformChange}
                        onAccountChange={(accountId) => form.setData('account_id', accountId)}
                        platformError={form.errors.platform}
                        accountError={form.errors.account_id}
                        accountLabel={t('ticket.refund.select_account', { defaultValue: 'Chọn tài khoản thanh lý' })}
                        accountPlaceholder={t('ticket.refund.select_account_placeholder', { defaultValue: 'Chọn tài khoản' })}
                        disabled={form.processing}
                    />

                    {form.data.platform && form.data.account_id && (
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleAddAccount}
                                disabled={form.processing || selectedAccounts.includes(form.data.account_id)}
                            >
                                + {t('ticket.refund.add_account', { defaultValue: 'Thêm tài khoản' })}
                            </Button>
                        </div>
                    )}

                    {selectedAccounts.length > 0 && (
                        <div className="space-y-2">
                            <Label>{t('ticket.refund.selected_accounts', { defaultValue: 'Tài khoản đã chọn' })}</Label>
                            <div className="space-y-2">
                                {selectedAccounts.map((accountId) => {
                                    const account = accounts.find(acc => acc.account_id === accountId);
                                    return (
                                        <div key={accountId} className="flex items-center justify-between p-2 border rounded">
                                            <span>{account?.account_name} ({accountId})</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleRemoveAccount(accountId)}
                                                disabled={form.processing}
                                            >
                                                {t('common.delete', { defaultValue: 'Xóa' })}
                                            </Button>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                    {form.errors.account_ids && (
                        <p className="text-sm text-red-500">{form.errors.account_ids}</p>
                    )}

                    <div className="space-y-2">
                        <Label>{t('ticket.refund.liquidation_type', { defaultValue: 'Loại thanh lý' })}</Label>
                        <RadioGroup
                            value={form.data.liquidation_type}
                            onValueChange={(value) => form.setData('liquidation_type', value)}
                            disabled={form.processing}
                        >
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value={REFUND_TYPE_WITHDRAW_TO_WALLET} id="withdraw_to_wallet" />
                                <Label htmlFor="withdraw_to_wallet" className="font-normal cursor-pointer">
                                    {t('ticket.refund.withdraw_to_wallet', { defaultValue: 'Rút Tiền Về Ví' })}
                                </Label>
                            </div>
                        </RadioGroup>
                        {form.data.liquidation_type === REFUND_TYPE_WITHDRAW_TO_WALLET && (
                            <div className="ml-6 space-y-1 text-sm text-muted-foreground">
                                <p>{t('ticket.refund.processing_time', { defaultValue: 'Thời gian xử lý 3 ngày làm việc' })}</p>
                                <p>{t('ticket.refund.amount_warning', { defaultValue: 'Số tiền thực tế có thể lệch do hệ thống facebook cập nhật chậm' })}</p>
                            </div>
                        )}
                        {form.errors.liquidation_type && (
                            <p className="text-sm text-red-500">{form.errors.liquidation_type}</p>
                        )}
                    </div>

                    {/* Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('ticket.refund.notes', { defaultValue: 'Ghi chú' })}
                            <span className="text-red-500 ml-1">*</span>
                        </Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder={t('ticket.refund.notes_placeholder', { defaultValue: 'Nhập mô tả vấn đề' })}
                            rows={4}
                            disabled={form.processing}
                        />
                        {form.errors.notes && (
                            <p className="text-sm text-red-500">{form.errors.notes}</p>
                        )}
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                                : t('ticket.refund.send_request', { defaultValue: 'Gửi yêu cầu' })}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

