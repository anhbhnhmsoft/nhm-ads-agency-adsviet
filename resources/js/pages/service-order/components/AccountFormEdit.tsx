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
import { Plus, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

type AccountFormEditProps = {
    account: AccountFormData;
    accountIndex: number;
    platform: number;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
    onUpdate: (index: number, data: AccountFormData) => void;
    onRemove: (index: number) => void;
    canRemove: boolean;
};

export const AccountFormEdit = ({
    account,
    accountIndex,
    platform,
    metaTimezones = [],
    googleTimezones = [],
    onUpdate,
    onRemove,
    canRemove,
}: AccountFormEditProps) => {
    const { t } = useTranslation();
    const isMeta = platform === _PlatformType.META;

    const [localBmIds, setLocalBmIds] = useState<string[]>(
        account.bm_ids || [],
    );

    useEffect(() => {
        setLocalBmIds(account.bm_ids || []);
    }, [account.bm_ids, accountIndex]);

    const updateField = <K extends keyof AccountFormData>(
        field: K,
        value: AccountFormData[K],
    ) => {
        const updatedAccount = { ...account, [field]: value };
        onUpdate(accountIndex, updatedAccount);
    };

    const commitBmIds = () => {
        const cleaned = (localBmIds || [])
            .map((id) => id?.trim())
            .filter((id): id is string => !!id);
        updateField('bm_ids', cleaned);
    };

    const addBmId = () => {
        setLocalBmIds((prev) => {
            const base = prev && prev.length > 0 ? [...prev] : [''];
            if (base.length >= 3) return base;
            return [...base, ''];
        });
    };

    const removeBmId = (index: number) => {
        setLocalBmIds((prev) => {
            const next = prev.filter((_, i) => i !== index);
            const cleaned = next
                .map((id) => id?.trim())
                .filter((id): id is string => !!id);
            updateField('bm_ids', cleaned);
            return next;
        });
    };

    const updateBmId = (index: number, value: string) => {
        setLocalBmIds((prev) => {
            const base = prev && prev.length > 0 ? [...prev] : [''];
            const next = [...base];
            next[index] = value;
            return next;
        });
    };

    return (
        <div className="space-y-4 rounded-lg border bg-gray-50 p-4">
            <div className="flex items-center justify-between">
                <h4 className="text-sm font-medium">
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
                        className="h-7 text-red-600 hover:text-red-700"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor={`edit_meta_email_${accountIndex}`}>
                    {t('service_purchase.meta_email')}:
                </Label>
                <Input
                    id={`edit_meta_email_${accountIndex}`}
                    type="email"
                    placeholder="abc123@gmail.com"
                    value={account.meta_email || ''}
                    onChange={(e) => updateField('meta_email', e.target.value)}
                />
            </div>

            <div className="space-y-2">
                <Label htmlFor={`edit_display_name_${accountIndex}`}>
                    {t('service_purchase.display_name')}:
                </Label>
                <Input
                    id={`edit_display_name_${accountIndex}`}
                    type="text"
                    placeholder="abc"
                    value={account.display_name || ''}
                    onChange={(e) =>
                        updateField('display_name', e.target.value)
                    }
                />
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>
                        {isMeta
                            ? t('service_purchase.id_bm', {
                                  defaultValue: 'ID BM',
                              })
                            : t('service_purchase.id_mcc', {
                                  defaultValue: 'ID MCC',
                              })}
                        :
                    </Label>
                    {(localBmIds?.length || 0) < 3 && (
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
                    )}
                </div>
                {(localBmIds && localBmIds.length > 0 ? localBmIds : ['']).map(
                    (bmId, idx) => (
                        <div key={idx} className="flex gap-2">
                            <Input
                                type="text"
                                placeholder="1234567890"
                                value={bmId}
                                onChange={(e) =>
                                    updateBmId(idx, e.target.value)
                                }
                                onBlur={commitBmIds}
                            />
                            {localBmIds.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeBmId(idx)}
                                    className="h-9 text-red-600"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    ),
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor={`edit_asset_access_${accountIndex}`}>
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
                    <SelectTrigger id={`edit_asset_access_${accountIndex}`}>
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
                <Label htmlFor={`edit_timezone_bm_${accountIndex}`}>
                    {isMeta
                        ? t('service_purchase.timezone_bm_label', {
                              defaultValue: 'Múi giờ BM',
                          })
                        : t('service_purchase.timezone_mcc_label', {
                              defaultValue: 'Múi giờ MCC',
                          })}
                </Label>
                <TimezoneSelect
                    id={`edit_timezone_bm_${accountIndex}`}
                    value={account.timezone_bm || ''}
                    onValueChange={(value) => updateField('timezone_bm', value)}
                    options={isMeta ? metaTimezones : googleTimezones}
                    placeholder={t('service_purchase.timezone_bm_placeholder', {
                        defaultValue: 'Chọn múi giờ',
                    })}
                />
            </div>
        </div>
    );
};
