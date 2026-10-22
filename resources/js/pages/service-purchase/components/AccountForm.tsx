import { TimezoneSelect } from '@/components/timezone-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { _PlatformType } from '@/lib/types/constants';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';
import { AlertTriangle, Plus, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

type AccountFormProps = {
    account: AccountFormData;
    accountIndex: number;
    platform: number;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
    onUpdate: (
        index: number,
        data: AccountFormData | ((prev: AccountFormData) => AccountFormData),
    ) => void;
    onRemove: (index: number) => void;
    canRemove: boolean;
    metaEmailError?: string;
};

export const AccountForm = ({
    account,
    accountIndex,
    platform,
    metaTimezones = [],
    googleTimezones = [],
    onUpdate,
    onRemove,
    canRemove,
    metaEmailError,
}: AccountFormProps) => {
    const { t } = useTranslation();
    const isMeta = platform === _PlatformType.META;

    const updateField = <K extends keyof AccountFormData>(
        field: K,
        value: AccountFormData[K],
    ) => {
        onUpdate(accountIndex, { ...account, [field]: value });
    };

    const addBmId = () => {
        const bmIds = account.bm_ids || [];
        updateField('bm_ids', [...bmIds, '']);
    };

    const removeBmId = (index: number) => {
        const bmIds = account.bm_ids || [];
        updateField(
            'bm_ids',
            bmIds.filter((_, i) => i !== index),
        );
    };


    return (
        <div className="space-y-4 rounded-lg border bg-white p-4">
            <div className="flex items-center justify-between">
                <h4 className="font-medium text-gray-800">
                    {t('service_purchase.account_number', {
                        number: accountIndex + 1,
                        defaultValue: `Tài khoản ${accountIndex + 1}`,
                    })}
                </h4>
                {canRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onRemove(accountIndex)}
                        className="text-red-600 hover:text-red-700"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor={`metaEmail_${accountIndex}`} className="flex flex-wrap items-center gap-1.5">
                    <span>{t('service_purchase.meta_email')}:</span>
                    <span className="text-[11px] font-normal text-muted-foreground">
                        ({t('common.optional', { defaultValue: 'Không bắt buộc' })} - {isMeta ? t('service_purchase.email_note_meta', { defaultValue: 'Nếu không có BM' }) : t('service_purchase.email_note_google', { defaultValue: 'Nếu không có MCC' })})
                    </span>
                </Label>
                <Input
                    id={`metaEmail_${accountIndex}`}
                    type="email"
                    placeholder="abc123@gmail.com"
                    value={account.meta_email || ''}
                    onChange={(e) => updateField('meta_email', e.target.value)}
                />
                {metaEmailError && (
                    <div className="mt-1 flex items-center gap-1 text-xs text-red-600">
                        <AlertTriangle className="h-3 w-3" />
                        <span>{metaEmailError}</span>
                    </div>
                )}
            </div>


            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label className="flex flex-wrap items-center gap-1.5">
                        <span>
                            {isMeta
                                ? t('service_purchase.id_bm', {
                                      defaultValue: 'ID BM',
                                  })
                                : t('service_purchase.id_mcc', {
                                      defaultValue: 'ID MCC',
                                  }) + ':'}
                        </span>
                        <span className="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                            ({t('common.recommended', { defaultValue: 'Khuyên dùng' })})
                        </span>
                    </Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addBmId}
                        className="h-7 text-xs"
                    >
                        <Plus className="mr-1 h-3 w-3" />
                        {t('service_purchase.add_bm_mcc', {
                            defaultValue: 'Thêm BM/MCC',
                        })}
                    </Button>
                </div>
                <div className="space-y-2">
                    {(account.bm_ids && account.bm_ids.length > 0
                        ? account.bm_ids
                        : ['']
                    ).map((bmId, idx) => (
                        <div
                            key={`bm-${accountIndex}-${idx}`}
                            className="flex gap-2"
                        >
                            <Input
                                id={`bm-id-${accountIndex}-${idx}`}
                                type="text"
                                placeholder="1234567890"
                                value={bmId}
                                onChange={(e) => {
                                    const newValue = e.target.value;
                                    const bmIds =
                                        account.bm_ids &&
                                        account.bm_ids.length > 0
                                            ? [...account.bm_ids]
                                            : [''];

                                    bmIds[idx] = newValue;

                                    onUpdate(accountIndex, {
                                        ...account,
                                        bm_ids: bmIds,
                                    });
                                }}
                            />
                            {account.bm_ids && account.bm_ids.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeBmId(idx)}
                                    className="text-red-600"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>



            <div className="space-y-2">
                <Label htmlFor={`asset_access_${accountIndex}`}>
                    {t('service_purchase.asset_access_label', {
                        defaultValue: 'Chia sẻ quyền truy cập',
                    })}
                </Label>
                <Select
                    value={account.asset_access || 'full_asset'}
                    onValueChange={(value: 'full_asset' | 'basic_asset') =>
                        updateField('asset_access', value)
                    }
                >
                    <SelectTrigger id={`asset_access_${accountIndex}`}>
                        <SelectValue
                            placeholder={t(
                                'service_purchase.asset_access_placeholder',
                                { defaultValue: 'Chọn quyền' },
                            )}
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="full_asset">
                            {t('service_purchase.asset_access_full', {
                                defaultValue: 'Full access',
                            })}
                        </SelectItem>
                        <SelectItem value="basic_asset">
                            {t('service_purchase.asset_access_basic', {
                                defaultValue: 'Basic access',
                            })}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label htmlFor={`timezone_bm_${accountIndex}`}>
                    {isMeta
                        ? t('service_purchase.timezone_bm_label', {
                              defaultValue: 'Múi giờ BM',
                          })
                        : t('service_purchase.timezone_mcc_label', {
                              defaultValue: 'Múi giờ MCC',
                          })}
                </Label>
                <TimezoneSelect
                    id={`timezone_bm_${accountIndex}`}
                    value={account.timezone_bm || ''}
                    onValueChange={(value) => updateField('timezone_bm', value)}
                    options={isMeta ? metaTimezones : googleTimezones}
                    placeholder={t('service_purchase.timezone_bm_placeholder', {
                        defaultValue: 'Chọn múi giờ',
                    })}
                />
                <p className="text-xs text-muted-foreground">
                    {t('service_purchase.timezone_bm_description', {
                        defaultValue:
                            'Múi giờ BM là múi giờ được sử dụng để tính toán thời gian và thời điểm của dịch vụ.',
                    })}
                </p>
            </div>
        </div>
    );
};
