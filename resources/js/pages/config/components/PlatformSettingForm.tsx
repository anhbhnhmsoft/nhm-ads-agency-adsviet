import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

export type FieldConfig = {
    key: string;
    label: string;
    type: 'text' | 'password' | 'textarea' | 'boolean' | 'number';
    required: boolean;
    placeholder?: string;
    description?: string;
    default_value?: string | boolean | number;
};

type Props = {
    data: {
        name: string;
        platform: number;
        config: Record<string, any>;
        disabled: boolean;
    };
    setData: (key: string, value: any) => void;
    onPlatformChange?: (platform: number) => void;
    processing: boolean;
    errors: Record<string, string>;
    onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
    googleFields: FieldConfig[];
    metaFields: FieldConfig[];
};

export default function PlatformSettingForm({
    data,
    setData,
    onPlatformChange,
    processing,
    errors,
    onSubmit,
    googleFields,
    metaFields,
}: Props) {
    const { t } = useTranslation();

    const currentFields = useMemo(() => {
        return data.platform === 1 ? googleFields : metaFields;
    }, [data.platform, googleFields, metaFields]);

    const platformGuide = useMemo(() => {
        if (data.platform === 1) {
            return {
                title: t('platform.guide.google.title', {
                    defaultValue: 'Hướng dẫn lấy cấu hình Google Ads',
                }),
                items: [
                    t('platform.guide.google.developer_token', {
                        defaultValue:
                            'Developer Token: vào Google Ads MCC > Công cụ và cài đặt > Thiết lập > Trung tâm API, sau đó copy Developer Token đã được duyệt.',
                    }),
                    t('platform.guide.google.oauth_client', {
                        defaultValue:
                            'Client ID và Client Secret: vào Google Cloud Console > APIs & Services > Credentials, tạo OAuth Client ID rồi copy thông tin ứng dụng.',
                    }),
                    t('platform.guide.google.refresh_token', {
                        defaultValue:
                            'Refresh Token: dùng OAuth Playground hoặc luồng OAuth nội bộ với scope https://www.googleapis.com/auth/adwords, đăng nhập bằng tài khoản có quyền MCC rồi exchange authorization code để lấy refresh_token.',
                    }),
                    t('platform.guide.google.login_customer_id', {
                        defaultValue:
                            'Login Customer ID (MCC): lấy ID MCC gốc trong Google Ads, nhập dạng số liền không có dấu gạch ngang.',
                    }),
                ],
                note: t('platform.guide.google.note', {
                    defaultValue:
                        'Lưu ý: Google refresh token thường không có hạn cố định. Hệ thống kiểm tra bằng cách dùng refresh token xin access token tạm thời.',
                }),
            };
        }

        return {
            title: t('platform.guide.meta.title', {
                defaultValue: 'Hướng dẫn lấy cấu hình Meta Ads',
            }),
            items: [
                t('platform.guide.meta.app_info', {
                    defaultValue:
                        'App ID và App Secret: vào Meta for Developers > My Apps > chọn app > Settings > Basic để copy thông tin ứng dụng.',
                }),
                t('platform.guide.meta.access_token', {
                    defaultValue:
                        'Access Token: tạo long-lived User Access Token hoặc System User Token có quyền ads_read, ads_management và business_management.',
                }),
                t('platform.guide.meta.sync_all', {
                    defaultValue:
                        'Nếu muốn đồng bộ tất cả Business portfolios mà VIA/User truy cập được, dùng User Access Token và bật tùy chọn đồng bộ tất cả Business portfolios.',
                }),
                t('platform.guide.meta.business_manager_id', {
                    defaultValue:
                        'Business Manager ID: lấy trong Business Settings > Business Info. Có thể để trống khi đồng bộ tất cả BM từ User token.',
                }),
            ],
            note: t('platform.guide.meta.note', {
                defaultValue:
                    'Lưu ý: Meta token có thể trả về thời điểm hết hạn, hệ thống sẽ hiển thị số ngày còn lại sau khi kiểm tra token/key.',
            }),
        };
    }, [data.platform, t]);

    const handleConfigChange = (key: string, value: any) => {
        const newConfig = { ...data.config, [key]: value };
        setData('config', newConfig);
    };

    const isTruthyConfigValue = (value: unknown) => {
        return (
            value === true || value === 1 || value === '1' || value === 'true'
        );
    };

    useEffect(() => {
        const nextConfig = { ...data.config };
        let changed = false;

        currentFields.forEach((field) => {
            if (
                field.default_value === undefined ||
                nextConfig[field.key] !== undefined
            ) {
                return;
            }

            if (field.key === 'sync_all_accessible_businesses') {
                nextConfig[field.key] = !nextConfig.business_manager_id;
            } else {
                nextConfig[field.key] = field.default_value;
            }
            changed = true;
        });

        if (changed) {
            setData('config', nextConfig);
        }
    }, [currentFields, data.config, setData]);

    const renderField = (field: FieldConfig) => {
        const value = data.config[field.key] ?? field.default_value ?? '';
        const fieldId = `config_${field.key}`;
        const syncAllAccessibleBusinesses =
            data.platform === 2 &&
            isTruthyConfigValue(data.config.sync_all_accessible_businesses);
        const isBusinessManagerScopeField =
            data.platform === 2 && field.key === 'business_manager_id';

        switch (field.type) {
            case 'password':
            case 'text':
                return (
                    <div key={field.key} className="grid gap-1">
                        <label htmlFor={fieldId}>
                            {field.label}
                            {field.required && (
                                <span className="ml-1 text-red-500">*</span>
                            )}
                        </label>
                        <Input
                            id={fieldId}
                            type={field.type}
                            value={String(value)}
                            onChange={(e) =>
                                handleConfigChange(field.key, e.target.value)
                            }
                            placeholder={field.placeholder}
                            required={field.required}
                            disabled={
                                isBusinessManagerScopeField &&
                                syncAllAccessibleBusinesses
                            }
                        />
                        {field.description && (
                            <span className="text-sm text-gray-500">
                                {field.description}
                            </span>
                        )}
                        {errors[`config.${field.key}`] && (
                            <span className="text-sm text-red-500">
                                {errors[`config.${field.key}`]}
                            </span>
                        )}
                    </div>
                );

            case 'textarea':
                return (
                    <div key={field.key} className="col-span-full grid gap-1">
                        <label htmlFor={fieldId}>
                            {field.label}
                            {field.required && (
                                <span className="ml-1 text-red-500">*</span>
                            )}
                        </label>
                        <Textarea
                            id={fieldId}
                            value={
                                Array.isArray(value)
                                    ? value.join('\n')
                                    : String(value)
                            }
                            onChange={(e) => {
                                const val = e.target.value;
                                // Nếu là array field (như customer_ids, ad_account_ids), split by newline
                                if (
                                    field.key.includes('_ids') ||
                                    field.key.includes('ids')
                                ) {
                                    handleConfigChange(
                                        field.key,
                                        val.split('\n').filter(Boolean),
                                    );
                                } else {
                                    handleConfigChange(field.key, val);
                                }
                            }}
                            placeholder={field.placeholder}
                            required={field.required}
                            className="min-h-24"
                        />
                        {field.description && (
                            <span className="text-sm text-gray-500">
                                {field.description}
                            </span>
                        )}
                        {errors[`config.${field.key}`] && (
                            <span className="text-sm text-red-500">
                                {errors[`config.${field.key}`]}
                            </span>
                        )}
                    </div>
                );

            case 'boolean':
                return (
                    <div
                        key={field.key}
                        className="col-span-full flex items-center gap-2"
                    >
                        <input
                            id={fieldId}
                            type="checkbox"
                            checked={isTruthyConfigValue(value)}
                            onChange={(e) =>
                                handleConfigChange(field.key, e.target.checked)
                            }
                        />
                        <label htmlFor={fieldId}>
                            {field.label}
                            {field.description && (
                                <span className="ml-2 text-sm text-gray-500">
                                    ({field.description})
                                </span>
                            )}
                        </label>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={onSubmit}
        >
            <div className="grid gap-1">
                <label>{t('common.foundation')}</label>
                <select
                    className="h-9 rounded-md border px-3"
                    value={data.platform}
                    onChange={(e) => {
                        const newPlatform = Number(e.target.value);
                        setData('platform', newPlatform);
                        // Gọi callback để parent load data
                        onPlatformChange?.(newPlatform);
                    }}
                    required
                >
                    <option value={1}>Google Ads</option>
                    <option value={2}>Meta Ads</option>
                </select>
            </div>

            <div className="grid gap-1">
                <label>
                    {t('platform.name', {
                        defaultValue: 'Tên BM/MCC (Gợi nhớ)',
                    })}
                    <span className="ml-1 text-red-500">*</span>
                </label>
                <Input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder={t('platform.placeholder_name', {
                        defaultValue: 'Ví dụ: BM Tài khoản 01',
                    })}
                    required
                />
                {errors.name && (
                    <span className="text-sm text-red-500">{errors.name}</span>
                )}
            </div>

            <div className="col-span-full rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                <div className="mb-2 font-semibold">{platformGuide.title}</div>
                <ol className="list-inside list-decimal space-y-1 leading-6">
                    {platformGuide.items.map((item, index) => (
                        <li key={index}>{item}</li>
                    ))}
                </ol>
                <p className="mt-2 text-xs leading-5 text-blue-700">
                    {platformGuide.note}
                </p>
            </div>

            {currentFields.map(renderField)}

            <div className="col-span-full flex items-center gap-2">
                <input
                    id="disabled"
                    type="checkbox"
                    checked={!!data.disabled}
                    onChange={(e) => setData('disabled', e.target.checked)}
                />
                <label htmlFor="disabled">{t('common.disabled')}</label>
            </div>

            <div className="col-span-full flex items-center gap-2">
                <Button type="submit" disabled={processing}>
                    {t('common.save')}
                </Button>
            </div>
        </form>
    );
}
