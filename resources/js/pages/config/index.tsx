import { ReactNode } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/input-error';
import { _ConfigName, configNameLabel } from '@/lib/types/constants';
import { config_update } from '@/routes';
import type { ConfigItem } from '@/pages/config/types/type';

type Props = {
    configs: Record<string, ConfigItem>;
};

const ConfigIndex = ({ configs }: Props) => {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        configs: Object.fromEntries(
            Object.entries(configs).map(([key, item]) => [key, item.value])
        ),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(config_update().url);
    };

    return (
        <div>
            <h1 className="text-xl font-semibold">
                {t('menu.crypto_wallet_config', { defaultValue: 'Cấu hình ví Crypto' })}
            </h1>
            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>{t('config.title', { defaultValue: 'Cấu hình địa chỉ ví' })}</CardTitle>
                    <CardDescription>
                        {t('config.description', { defaultValue: 'Cấu hình địa chỉ ví để nhận tiền từ giao dịch crypto' })}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor={_ConfigName.BEP20_WALLET_ADDRESS}>
                                    {t(configNameLabel[_ConfigName.BEP20_WALLET_ADDRESS], {
                                        defaultValue: 'Địa chỉ ví BEP20',
                                    })}
                                </Label>
                                <Input
                                    id={_ConfigName.BEP20_WALLET_ADDRESS}
                                    value={data.configs[_ConfigName.BEP20_WALLET_ADDRESS] || ''}
                                    onChange={(e) =>
                                        setData('configs', {
                                            ...data.configs,
                                            [_ConfigName.BEP20_WALLET_ADDRESS]: e.target.value,
                                        })
                                    }
                                    placeholder={t('config.bep20_placeholder', {
                                        defaultValue: 'Nhập địa chỉ ví Binance Smart Chain (BEP20)',
                                    })}
                                />
                                <InputError message={errors[`configs.${_ConfigName.BEP20_WALLET_ADDRESS}`]} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor={_ConfigName.TRC20_WALLET_ADDRESS}>
                                    {t(configNameLabel[_ConfigName.TRC20_WALLET_ADDRESS], {
                                        defaultValue: 'Địa chỉ ví TRC20',
                                    })}
                                </Label>
                                <Input
                                    id={_ConfigName.TRC20_WALLET_ADDRESS}
                                    value={data.configs[_ConfigName.TRC20_WALLET_ADDRESS] || ''}
                                    onChange={(e) =>
                                        setData('configs', {
                                            ...data.configs,
                                            [_ConfigName.TRC20_WALLET_ADDRESS]: e.target.value,
                                        })
                                    }
                                    placeholder={t('config.trc20_placeholder', {
                                        defaultValue: 'Nhập địa chỉ ví Tron (TRC20)',
                                    })}
                                />
                                <InputError message={errors[`configs.${_ConfigName.TRC20_WALLET_ADDRESS}`]} />
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? t('common.saving', { defaultValue: 'Đang lưu...' })
                                    : t('common.save_changes', { defaultValue: 'Lưu thay đổi' })}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
};

ConfigIndex.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'menu.crypto_wallet_config' }]} children={page} />
);

export default ConfigIndex;

