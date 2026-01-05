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

type AccountFormProps = {
    account: AccountFormData;
    accountIndex: number;
    platform: number;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
    onUpdate: (index: number, data: AccountFormData | ((prev: AccountFormData) => AccountFormData)) => void;
    onRemove: (index: number) => void;
    canRemove: boolean;
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
}: AccountFormProps) => {
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

    // Fix phần hiển thị input website khi mảng websites rỗng
    // Chỉ hiển thị một input (array length === 0), khi có giá trị thì convert sang dạng mảng

    return (
        <div className="space-y-4 p-4 border rounded-lg bg-white">
            <div className="flex items-center justify-between">
                <h4 className="font-medium text-gray-800">
                    {t('service_purchase.account_number', { number: accountIndex + 1, defaultValue: `Tài khoản ${accountIndex + 1}` })}
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
                <Label htmlFor={`metaEmail_${accountIndex}`}>
                    {t('service_purchase.meta_email') + ':'}
                </Label>
                <Input
                    id={`metaEmail_${accountIndex}`}
                    type="email"
                    placeholder="abc123@gmail.com"
                    value={account.meta_email || ''}
                    onChange={(e) => updateField('meta_email', e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                    {isMeta
                        ? t('service_purchase.email_note_meta', { defaultValue: 'Nếu không có BM' })
                        : t('service_purchase.email_note_google', { defaultValue: 'Nếu không có MCC' })}
                </p>
            </div>

            <div className="space-y-2">
                <Label htmlFor={`displayName_${accountIndex}`}>
                    {t('service_purchase.display_name') + ':'}
                </Label>
                <Input
                    id={`displayName_${accountIndex}`}
                    type="text"
                    placeholder="abc"
                    value={account.display_name || ''}
                    onChange={(e) => updateField('display_name', e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                    {t('service_purchase.display_name_note', { defaultValue: 'Bạn có thể đặt tên của bạn' })}
                </p>
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>
                        {isMeta
                            ? t('service_purchase.id_bm', { defaultValue: 'ID BM' })
                            : t('service_purchase.id_mcc', { defaultValue: 'ID MCC' }) + ':'}
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
                <div className="space-y-2">
                    {(account.bm_ids && account.bm_ids.length > 0 ? account.bm_ids : ['']).map((bmId, idx) => (
                        <div key={`bm-${accountIndex}-${idx}`} className="flex gap-2">
                            <Input
                                id={`bm-id-${accountIndex}-${idx}`}
                                type="text"
                                placeholder="1234567890"
                                value={bmId}
                                onChange={(e) => {
                                    const newValue = e.target.value;
                                    const bmIds =
                                        account.bm_ids && account.bm_ids.length > 0
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
                {isMeta && (
                    <>
                        <div className="flex items-center justify-between">
                            <Label>
                                {t('service_purchase.info_fanpage', { defaultValue: 'Thông tin fanpage' }) + ':'}
                            </Label>
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
                        <div className="space-y-2">
                            {(account.fanpages && account.fanpages.length > 0 ? account.fanpages : ['']).map((fanpage, idx) => (
                                <div key={`fanpage-${accountIndex}-${idx}`} className="flex gap-2">
                                    <Input
                                        id={`fanpage-${accountIndex}-${idx}`}
                                        type="text"
                                        placeholder={t('service_purchase.info_fanpage_placeholder', {
                                            defaultValue: 'Link hoặc tên fanpage',
                                        })}
                                        value={fanpage}
                                        onChange={(e) => {
                                            const newValue = e.target.value;

                                            const fanpages =
                                                account.fanpages && account.fanpages.length > 0
                                                    ? [...account.fanpages]
                                                    : [''];

                                            fanpages[idx] = newValue;

                                            onUpdate(accountIndex, {
                                                ...account,
                                                fanpages,
                                            });
                                        }}
                                    />

                                    {account.fanpages && account.fanpages.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeFanpage(idx)}
                                            className="text-red-600"
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>
                        {t('service_purchase.info_website', { defaultValue: 'Thông tin website' }) + ':'}
                    </Label>
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
                <div className="space-y-2">
                    {(account.websites && account.websites.length > 0
                        ? account.websites
                        : ['']
                    ).map((website, idx) => (
                        <div key={`website-${accountIndex}-${idx}`} className="flex gap-2">
                            <Input
                                id={`website-${accountIndex}-${idx}`}
                                type="text"
                                placeholder={t('service_purchase.info_website_placeholder', { defaultValue: 'Link website' })}
                                value={website || ''}
                                onChange={(e) => {
                                    const newValue = e.target.value;
                                    let websites = account.websites && account.websites.length > 0 ? [...account.websites] : [''];
                                    websites[idx] = newValue;
                                    // Nếu là input đầu tiên và tất cả empty, thay [] thành [""] -> []
                                    // Nếu tất cả các input đều trống, thì chuyển thành mảng rỗng (người dùng xóa hết)
                                    // Nếu có 1 input và input rỗng, thì để ""
                                    // Nếu có 1 input và có giá trị, thì thành [giá trị]
                                    // Nếu có nhiều input thì giữ nguyên
                                    // Always trim trailing empty websites
                                    websites = websites.filter((url, i, arr) => !(arr.length === 1 && url === ''));
                                    // Không cho phép nhiều input đều rỗng
                                    onUpdate(accountIndex, {
                                        ...account,
                                        websites,
                                    });
                                }}
                            />
                            {(account.websites && account.websites.length > 1) && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeWebsite(idx)}
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
                    {t('service_purchase.asset_access_label', { defaultValue: 'Chia sẻ quyền truy cập' })}
                </Label>
                <Select
                    value={account.asset_access || 'full_asset'}
                    onValueChange={(value: 'full_asset' | 'basic_asset') => updateField('asset_access', value)}
                >
                    <SelectTrigger id={`asset_access_${accountIndex}`}>
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
                <Label htmlFor={`timezone_bm_${accountIndex}`}>
                    {isMeta
                        ? t('service_purchase.timezone_bm_label', { defaultValue: 'Múi giờ BM' })
                        : t('service_purchase.timezone_mcc_label', { defaultValue: 'Múi giờ MCC' })}
                </Label>
                <TimezoneSelect
                    id={`timezone_bm_${accountIndex}`}
                    value={account.timezone_bm || ''}
                    onValueChange={(value) => updateField('timezone_bm', value)}
                    options={isMeta ? metaTimezones : googleTimezones}
                    placeholder={t('service_purchase.timezone_bm_placeholder', { defaultValue: 'Chọn múi giờ' })}
                />
                <p className="text-xs text-muted-foreground">
                    {t('service_purchase.timezone_bm_description', { defaultValue: 'Múi giờ BM là múi giờ được sử dụng để tính toán thời gian và thời điểm của dịch vụ.' })}
                </p>
            </div>
        </div>
    );
};