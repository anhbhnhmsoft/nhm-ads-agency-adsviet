import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { _PlatformType } from '@/lib/types/constants';
import PlatformSettingForm, {
    FieldConfig,
} from '@/pages/config/components/PlatformSettingForm';
import { usePlatformForm } from '@/pages/config/hooks/use-platform-form';
import {
    platform_settings_destroy,
    platform_settings_get_by_platform,
    platform_settings_store,
    platform_settings_update,
} from '@/routes';
import axios from 'axios';
import { Plus, RefreshCw, X } from 'lucide-react';
import { ReactNode, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    googleFields: FieldConfig[];
    metaFields: FieldConfig[];
};

const ListPlatformSettings = ({ googleFields, metaFields }: Props) => {
    const { t } = useTranslation();
    const [settingsList, setSettingsList] = useState<
        {
            id: string;
            name: string;
            platform: number;
            config: Record<string, any>;
            token_status?: {
                status?: string;
                message?: string;
                expires_label?: string;
                expires_at?: string | null;
                checked_at?: string;
            };
            disabled: boolean;
        }[]
    >([]);
    const [currentSetting, setCurrentSetting] = useState<{
        id: string;
        name: string;
        platform: number;
        config: Record<string, any>;
        token_status?: {
            status?: string;
            message?: string;
            expires_label?: string;
            expires_at?: string | null;
            checked_at?: string;
        };
        disabled: boolean;
    } | null>(null);
    const [showForm, setShowForm] = useState(false);
    const [loading, setLoading] = useState(false);
    const [filterPlatform, setFilterPlatform] = useState<'all' | number>('all');

    const { form } = usePlatformForm({
        initial: {
            name: '',
            platform: _PlatformType.GOOGLE,
            config: {},
            disabled: false,
        },
        storeUrl: platform_settings_store.url(),
    });
    const { data, setData, processing, errors, post, put } = form;

    useEffect(() => {
        loadPlatformData();
    }, []);

    // Handler khi platform thay đổi (nếu có dùng filter, hiện tại đang hiện ALL)
    const handlePlatformChange = (platform: number) => {
        setData('platform', platform);
    };

    const loadPlatformData = async () => {
        setLoading(true);
        try {
            // Fetch đồng thời cả 2 nền tảng để hiện Full danh sách
            const [googleRes, metaRes] = await Promise.all([
                axios.get(
                    platform_settings_get_by_platform.url({
                        platform: _PlatformType.GOOGLE,
                    }),
                ),
                axios.get(
                    platform_settings_get_by_platform.url({
                        platform: _PlatformType.META,
                    }),
                ),
            ]);

            const googleList = (googleRes.data?.data ?? []) as any[];
            const metaList = (metaRes.data?.data ?? []) as any[];

            setSettingsList([...googleList, ...metaList]);
        } catch (error) {
            setSettingsList([]);
        } finally {
            setLoading(false);
        }
    };

    const filteredSettings = useMemo(() => {
        if (filterPlatform === 'all') return settingsList;
        return settingsList.filter((item) => item.platform === filterPlatform);
    }, [settingsList, filterPlatform]);

    const handleEdit = (setting: any) => {
        setCurrentSetting(setting);
        setShowForm(true);
        setData({
            name: setting.name || '',
            platform: Number(setting.platform),
            config: (setting.config ?? {}) as Record<string, any>,
            token_status: setting.token_status,
            disabled: Boolean(setting.disabled),
        });
        // Scroll lên đầu trang để anh thấy Form
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleAddNew = () => {
        handleResetForm();
        setShowForm(true);
    };

    const handleResetForm = (platform?: number) => {
        setCurrentSetting(null);
        setData({
            name: '',
            platform: platform ?? data.platform,
            config: {},
            disabled: false,
        });
    };

    const handleFormSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (currentSetting) {
            put(platform_settings_update.url({ id: currentSetting.id }), {
                onSuccess: () => {
                    loadPlatformData();
                    setShowForm(false);
                },
            });
        } else {
            post(platform_settings_store.url(), {
                onSuccess: () => {
                    loadPlatformData();
                    setShowForm(false);
                },
            });
        }
    };

    const handleDelete = (id: string, name: string) => {
        if (
            window.confirm(
                t('platform.confirm_delete', {
                    defaultValue: `Bạn có chắc muốn xóa cấu hình '${name}'?`,
                }),
            )
        ) {
            form.delete(platform_settings_destroy.url({ id }), {
                onSuccess: () => {
                    loadPlatformData();
                },
            });
        }
    };

    const handleCheckToken = async (setting: {
        id: string;
        name: string;
        platform: number;
    }) => {
        try {
            setLoading(true);
            const response = await axios.post(
                `/platform-settings/${setting.id}/check-token`,
            );
            const nextTokenStatus = response.data?.data?.token_status ?? null;

            setSettingsList((prev) =>
                prev.map((item) =>
                    item.id === setting.id
                        ? {
                              ...item,
                              config: {
                                  ...item.config,
                                  token_status: nextTokenStatus,
                              },
                              token_status: nextTokenStatus,
                          }
                        : item,
                ),
            );

            if (currentSetting?.id === setting.id) {
                setCurrentSetting((prev) =>
                    prev
                        ? {
                              ...prev,
                              config: {
                                  ...prev.config,
                                  token_status: nextTokenStatus,
                              },
                              token_status: nextTokenStatus,
                          }
                        : prev,
                );
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-semibold">
                    {t('menu.platform_settings', {
                        defaultValue: 'Cấu hình nền tảng',
                    })}
                </h1>
                {!showForm && (
                    <Button
                        onClick={handleAddNew}
                        variant="default"
                        className="shadow-sm"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        {t('platform.add_new', {
                            defaultValue: 'Thêm BM/MCC mới',
                        })}
                    </Button>
                )}
            </div>

            {showForm && (
                <Card className="border-primary/20 shadow-lg">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>
                                {currentSetting
                                    ? t('platform.edit_title', {
                                          defaultValue: 'Chỉnh sửa cấu hình',
                                      })
                                    : t('platform.create_title', {
                                          defaultValue: 'Thêm cấu hình mới',
                                      })}
                            </CardTitle>
                            <CardDescription>
                                {t('platform.description', {
                                    defaultValue:
                                        'Nhập thông tin chi tiết cho BM/MCC',
                                })}
                            </CardDescription>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowForm(false)}
                            className="h-8 border-gray-200 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        >
                            <X className="mr-2 h-4 w-4" />
                            {t('common.close', { defaultValue: 'Đóng' })}
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <PlatformSettingForm
                            data={data}
                            setData={setData}
                            onPlatformChange={handlePlatformChange}
                            processing={processing}
                            errors={errors}
                            onSubmit={handleFormSubmit}
                            googleFields={googleFields}
                            metaFields={metaFields}
                        />
                    </CardContent>
                </Card>
            )}

            {!showForm && (
                <div className="mb-2 flex flex-wrap gap-2">
                    <Button
                        variant={
                            filterPlatform === 'all' ? 'default' : 'outline'
                        }
                        size="sm"
                        onClick={() => setFilterPlatform('all')}
                    >
                        {t('common.all', { defaultValue: 'Tất cả' })}
                    </Button>
                    <Button
                        variant={
                            filterPlatform === _PlatformType.META
                                ? 'default'
                                : 'outline'
                        }
                        size="sm"
                        onClick={() => setFilterPlatform(_PlatformType.META)}
                    >
                        {t('enum.PlatformType.META', {
                            defaultValue: 'Facebook',
                        })}
                    </Button>
                    <Button
                        variant={
                            filterPlatform === _PlatformType.GOOGLE
                                ? 'default'
                                : 'outline'
                        }
                        size="sm"
                        onClick={() => setFilterPlatform(_PlatformType.GOOGLE)}
                    >
                        {t('enum.PlatformType.GOOGLE', {
                            defaultValue: 'Google',
                        })}
                    </Button>
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>
                        {t('platform.list_title', {
                            defaultValue: 'Danh sách cấu hình hiện có',
                        })}
                    </CardTitle>
                    <CardDescription>
                        {t('platform.list_description', {
                            defaultValue:
                                'Chọn cấu hình bên dưới để chỉnh sửa thông tin',
                        })}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">
                                        {t('platform.col_name', {
                                            defaultValue: 'Tên BM/MCC',
                                        })}
                                    </th>
                                    <th className="px-4 py-3">
                                        {t('platform.col_platform', {
                                            defaultValue: 'Nền tảng',
                                        })}
                                    </th>
                                    <th className="px-4 py-3">
                                        {t('platform.col_status', {
                                            defaultValue: 'Trạng thái',
                                        })}
                                    </th>
                                    <th className="px-4 py-3">
                                        {t('platform.col_token_life', {
                                            defaultValue: 'Thời gian sống',
                                        })}
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        {t('common.action')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {loading && settingsList.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="py-4 text-center"
                                        >
                                            {t('common.loading')}
                                        </td>
                                    </tr>
                                ) : filteredSettings.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="py-4 text-center text-muted-foreground"
                                        >
                                            {t('common.no_data')}
                                        </td>
                                    </tr>
                                ) : (
                                    filteredSettings.map((item) => (
                                        <tr
                                            key={item.id}
                                            className={
                                                currentSetting?.id === item.id
                                                    ? 'bg-primary/5'
                                                    : ''
                                            }
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                <div className="flex flex-col">
                                                    <span className="flex items-center gap-1">
                                                        <span className="font-bold text-muted-foreground">
                                                            {item.platform ===
                                                            _PlatformType.GOOGLE
                                                                ? 'MCC - '
                                                                : 'BM - '}
                                                        </span>
                                                        {item.name}
                                                    </span>
                                                    <span className="text-xs font-normal text-muted-foreground">
                                                        ID: {item.id}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${item.platform === _PlatformType.GOOGLE ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}`}
                                                >
                                                    {item.platform ===
                                                    _PlatformType.GOOGLE
                                                        ? 'Google'
                                                        : 'Meta'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.disabled ? (
                                                    <span className="rounded-full bg-red-100 px-2 py-1 text-xs text-red-700">
                                                        {t('common.disabled')}
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">
                                                        {t('common.active')}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-col gap-1 text-xs text-muted-foreground">
                                                    <span>
                                                        {item.token_status?.expires_label ||
                                                            t('platform.token_not_checked', {
                                                                defaultValue:
                                                                    'Chưa kiểm tra',
                                                            })}
                                                    </span>
                                                    {item.token_status?.checked_at && (
                                                        <span>
                                                            {t('platform.token_checked_at', {
                                                                defaultValue:
                                                                    'Kiểm tra lúc: {{time}}',
                                                                time: new Date(
                                                                    item.token_status.checked_at,
                                                                ).toLocaleString('vi-VN'),
                                                            })}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleCheckToken(
                                                                item,
                                                            )
                                                        }
                                                        disabled={loading}
                                                    >
                                                        <RefreshCw className="mr-2 h-4 w-4" />
                                                        {t('platform.check_token', {
                                                            defaultValue:
                                                                'Kiểm tra token/key',
                                                        })}
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleEdit(item)
                                                        }
                                                    >
                                                        {t('common.edit')}
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-red-100 text-red-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                                        onClick={() =>
                                                            handleDelete(
                                                                item.id,
                                                                item.name,
                                                            )
                                                        }
                                                        disabled={processing}
                                                    >
                                                        {t('common.delete')}
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div className="flex flex-wrap gap-3">
                <Button asChild variant="outline">
                    <a
                        href="https://ads.google.com/aw/billing/home"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {t('platform.open_google_billing', {
                            defaultValue: 'Mở Billing Google Ads',
                        })}
                    </a>
                </Button>
                <Button asChild variant="outline">
                    <a
                        href="https://business.facebook.com/billing_hub/payment_settings"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {t('platform.open_meta_billing', {
                            defaultValue: 'Mở Billing Meta Ads',
                        })}
                    </a>
                </Button>
            </div>
        </div>
    );
};

ListPlatformSettings.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.platform_settings' }]}
        children={page}
    />
);

export default ListPlatformSettings;
