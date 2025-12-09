import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';
import { PlatformAccountSelector } from '../../components/PlatformAccountSelector';
import { useAppealForm } from '../hooks/use-appeal-form';
import type { AppealFormProps } from '../types/type';

export const AppealForm = ({ accounts, adminEmail }: AppealFormProps) => {
    const { t } = useTranslation();
    const { form, handleSubmit } = useAppealForm();

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
                <CardTitle>{t('ticket.appeal.create_request', { defaultValue: 'Tạo yêu cầu' })}</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Description Alert */}
                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            {adminEmail 
                                ? t('ticket.appeal.description', { 
                                    defaultValue: 'Khách hàng vui lòng mời AdsViet ({{email}}) vào BM chứa tài khoản quảng cáo cần kháng)',
                                    email: adminEmail 
                                })
                                : t('ticket.appeal.description_no_email', { 
                                    defaultValue: 'Khách hàng vui lòng mời AdsViet vào BM chứa tài khoản quảng cáo cần kháng)' 
                                })
                            }
                        </AlertDescription>
                    </Alert>

                    {/* Platform and Account Selection */}
                    <PlatformAccountSelector
                        accounts={accounts}
                        selectedPlatform={form.data.platform}
                        selectedAccountId={form.data.account_id}
                        onPlatformChange={handlePlatformChange}
                        onAccountChange={(accountId) => form.setData('account_id', accountId)}
                        platformError={form.errors.platform}
                        accountError={form.errors.account_id}
                        accountLabel={t('ticket.appeal.select_account', { defaultValue: 'Chọn tài khoản cần kháng' })}
                        accountPlaceholder={t('ticket.appeal.select_account_placeholder', { defaultValue: 'Chọn tài khoản' })}
                        disabled={form.processing}
                    />

                    {form.data.platform && (
                        <div className="text-sm text-muted-foreground">
                            <p>{t('ticket.appeal.processing_time', { defaultValue: '* Thời gian xử lý từ 3-5 ngày làm việc' })}</p>
                        </div>
                    )}

                    {/* Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('ticket.appeal.notes', { defaultValue: 'Ghi chú' })}
                            <span className="text-red-500 ml-1">*</span>
                        </Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder={t('ticket.appeal.notes_placeholder', { defaultValue: 'Nhập mô tả vấn đề cần kháng tài khoản' })}
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
                                : t('ticket.appeal.send_request', { defaultValue: 'Gửi yêu cầu' })}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

