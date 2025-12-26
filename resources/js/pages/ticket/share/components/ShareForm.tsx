import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';
import { PlatformAccountSelector } from '../../components/PlatformAccountSelector';
import { useShareForm } from '../hooks/use-share-form';
import type { ShareFormProps } from '../types/type';

export const ShareForm = ({ accounts }: ShareFormProps) => {
    const { t } = useTranslation();
    const { form, handleSubmit } = useShareForm();

    const handlePlatformChange = (platform: string) => {
        form.setData({
            ...form.data,
            platform: platform,
            account_id: '',
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('ticket.share.create_request', { defaultValue: 'Tạo yêu cầu' })}</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Platform and Account Selection */}
                    <PlatformAccountSelector
                        accounts={accounts}
                        selectedPlatform={form.data.platform}
                        selectedAccountId={form.data.account_id}
                        onPlatformChange={handlePlatformChange}
                        onAccountChange={(accountId) => form.setData('account_id', accountId)}
                        platformError={form.errors.platform}
                        accountError={form.errors.account_id}
                        accountLabel={t('ticket.share.select_account', { defaultValue: 'Chọn tài khoản' })}
                        accountPlaceholder={t('ticket.share.select_account_placeholder', { defaultValue: 'Chọn tài khoản' })}
                        disabled={form.processing}
                    />

                    {/* BM/BC/MCC ID */}
                    <div className="space-y-2">
                        <Label htmlFor="bm_bc_mcc_id">
                            {t('ticket.share.bm_bc_mcc_id', { defaultValue: 'ID BM/MCC' })}
                            <span className="text-red-500 ml-1">*</span>
                        </Label>
                        <Input
                            id="bm_bc_mcc_id"
                            value={form.data.bm_bc_mcc_id}
                            onChange={(e) => form.setData('bm_bc_mcc_id', e.target.value)}
                            placeholder={t('ticket.share.bm_bc_mcc_id_placeholder', { defaultValue: 'Nhập ID BM/MCC' })}
                            disabled={form.processing}
                        />
                        {form.errors.bm_bc_mcc_id && (
                            <p className="text-sm text-red-500">{form.errors.bm_bc_mcc_id}</p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            {t('ticket.share.domain_note', { defaultValue: 'Các tài khoản share cùng một BM/BC/MCC phải cùng chung một domain' })}
                        </p>
                    </div>

                    {/* Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('ticket.share.notes', { defaultValue: 'Ghi chú' })}
                            <span className="text-red-500 ml-1">*</span>
                        </Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder={t('ticket.share.notes_placeholder', { defaultValue: 'Nhập mô tả vấn đề' })}
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
                                : t('ticket.share.send_request', { defaultValue: 'Gửi yêu cầu' })}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

