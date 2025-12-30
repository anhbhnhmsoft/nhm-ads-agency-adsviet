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
import { TimezoneSelect } from '@/components/timezone-select';
import { X, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { _PlatformType } from '@/lib/types/constants';
import type { AccountFormData } from '@/pages/service-purchase/hooks/use-form';

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

    const updateField = <K extends keyof AccountFormData>(field: K, value: AccountFormData[K]) => {
        onUpdate(accountIndex, { ...account, [field]: value });
    };

    const addBmId = () => {
        const bmIds = account.bm_ids || [];
        if (bmIds.length < 3) {
            updateField('bm_ids', [...bmIds, '']);
        }
    };

    const removeBmId = (index: number) => {
        const bmIds = account.bm_ids || [];
        updateField('bm_ids', bmIds.filter((_, i) => i !== index));
    };

    const updateBmId = (index: number, value: string) => {
        const bmIds = account.bm_ids || [];
        const newBmIds = [...bmIds];
        newBmIds[index] = value;
        updateField('bm_ids', newBmIds);
    };

    const addFanpage = () => {
        const fanpages = account.fanpages || [];
        if (fanpages.length < 3) {
            updateField('fanpages', [...fanpages, '']);
        }
    };

    const removeFanpage = (index: number) => {
        const fanpages = account.fanpages || [];
        updateField('fanpages', fanpages.filter((_, i) => i !== index));
    };

    const updateFanpage = (index: number, value: string) => {
        const fanpages = account.fanpages || [];
        const newFanpages = [...fanpages];
        newFanpages[index] = value;
        updateField('fanpages', newFanpages);
    };

    const addWebsite = () => {
        const websites = account.websites || [];
        if (websites.length < 3) {
            updateField('websites', [...websites, '']);
        }
    };

    const removeWebsite = (index: number) => {
        const websites = account.websites || [];
        updateField('websites', websites.filter((_, i) => i !== index));
    };

    const updateWebsite = (index: number, value: string) => {
        const websites = account.websites || [];
        const newWebsites = [...websites];
        newWebsites[index] = value;
        updateField('websites', newWebsites);
    };

    return (
        <div className="space-y-4 p-4 border rounded-lg bg-gray-50">
            <div className="flex items-center justify-between">
                <h4 className="font-medium text-sm">
                    {t('service_purchase.account_number', { number: accountIndex + 1, defaultValue: `Tài khoản ${accountIndex + 1}` })}
                </h4>
                {canRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onRemove(accountIndex)}
                        className="text-red-600 hover:text-red-700 h-7"
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
                    onChange={(e) => updateField('display_name', e.target.value)}
                />
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>
                        {isMeta
                            ? t('service_purchase.id_bm', { defaultValue: 'ID BM' })
                            : t('service_purchase.id_mcc', { defaultValue: 'ID MCC' })}:
                    </Label>
                    {(account.bm_ids?.length || 0) < 3 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addBmId}
                            className="h-7 text-xs"
                        >
                            <Plus className="h-3 w-3 mr-1" />
                            {t('service_purchase.add_bm_mcc', { defaultValue: 'Thêm BM/MCC' })}
                        </Button>
                    )}
                </div>
                {account.bm_ids && account.bm_ids.length > 0 ? (
                    <div className="space-y-2">
                        {account.bm_ids.map((bmId, idx) => (
                            <div key={idx} className="flex gap-2">
                                <Input
                                    type="text"
                                    placeholder="1234567890"
                                    value={bmId}
                                    onChange={(e) => updateBmId(idx, e.target.value)}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeBmId(idx)}
                                    className="text-red-600 h-9"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <Input
                        type="text"
                        placeholder="1234567890"
                        value=""
                        onChange={(e) => updateField('bm_ids', [e.target.value])}
                    />
                )}
            </div>

            {isMeta && (
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <Label>{t('service_purchase.info_fanpage', { defaultValue: 'Thông tin fanpage' })}:</Label>
                        {(account.fanpages?.length || 0) < 3 && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addFanpage}
                                className="h-7 text-xs"
                            >
                                <Plus className="h-3 w-3 mr-1" />
                                {t('service_purchase.add_fanpage', { defaultValue: 'Thêm fanpage' })}
                            </Button>
                        )}
                    </div>
                    {account.fanpages && account.fanpages.length > 0 ? (
                        <div className="space-y-2">
                            {account.fanpages.map((fanpage, idx) => (
                                <div key={idx} className="flex gap-2">
                                    <Input
                                        type="text"
                                        placeholder={t('service_purchase.info_fanpage_placeholder', { defaultValue: 'Link hoặc tên fanpage' })}
                                        value={fanpage}
                                        onChange={(e) => updateFanpage(idx, e.target.value)}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeFanpage(idx)}
                                        className="text-red-600 h-9"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Input
                            type="text"
                            placeholder={t('service_purchase.info_fanpage_placeholder', { defaultValue: 'Link hoặc tên fanpage' })}
                            value=""
                            onChange={(e) => updateField('fanpages', [e.target.value])}
                        />
                    )}
                </div>
            )}

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>{t('service_purchase.info_website', { defaultValue: 'Thông tin website' })}:</Label>
                    {(account.websites?.length || 0) < 3 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addWebsite}
                            className="h-7 text-xs"
                        >
                            <Plus className="h-3 w-3 mr-1" />
                            {t('service_purchase.add_website', { defaultValue: 'Thêm website' })}
                        </Button>
                    )}
                </div>
                {account.websites && account.websites.length > 0 ? (
                    <div className="space-y-2">
                        {account.websites.map((website, idx) => (
                            <div key={idx} className="flex gap-2">
                                <Input
                                    type="text"
                                    placeholder={t('service_purchase.info_website_placeholder', { defaultValue: 'Link website' })}
                                    value={website}
                                    onChange={(e) => updateWebsite(idx, e.target.value)}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeWebsite(idx)}
                                    className="text-red-600 h-9"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <Input
                        type="text"
                        placeholder={t('service_purchase.info_website_placeholder', { defaultValue: 'Link website' })}
                        value=""
                        onChange={(e) => updateField('websites', [e.target.value])}
                    />
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor={`edit_asset_access_${accountIndex}`}>
                    {t('service_purchase.asset_access_label', { defaultValue: 'Chia sẻ quyền truy cập' })}
                </Label>
                <Select
                    value={account.asset_access || 'full_asset'}
                    onValueChange={(value: 'full_asset' | 'basic_asset') => updateField('asset_access', value)}
                >
                    <SelectTrigger id={`edit_asset_access_${accountIndex}`}>
                        <SelectValue placeholder={t('service_purchase.asset_access_placeholder', { defaultValue: 'Chọn quyền' })} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="full_asset">
                            {t('service_purchase.asset_access_full', { defaultValue: 'Full access' })}
                        </SelectItem>
                        <SelectItem value="basic_asset">
                            {t('service_purchase.asset_access_basic', { defaultValue: 'Basic access' })}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label htmlFor={`edit_timezone_bm_${accountIndex}`}>
                    {isMeta
                        ? t('service_purchase.timezone_bm_label', { defaultValue: 'Múi giờ BM' })
                        : t('service_purchase.timezone_mcc_label', { defaultValue: 'Múi giờ MCC' })}
                </Label>
                <TimezoneSelect
                    id={`edit_timezone_bm_${accountIndex}`}
                    value={account.timezone_bm || ''}
                    onValueChange={(value) => updateField('timezone_bm', value)}
                    options={isMeta ? metaTimezones : googleTimezones}
                    placeholder={t('service_purchase.timezone_bm_placeholder', { defaultValue: 'Chọn múi giờ' })}
                />
            </div>
        </div>
    );
};

