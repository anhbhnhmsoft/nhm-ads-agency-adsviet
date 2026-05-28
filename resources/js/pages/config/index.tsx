import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { _ConfigName, configNameLabel } from '@/lib/types/constants';
import type { ConfigItem } from '@/pages/config/types/type';
import { config_update } from '@/routes';
import { useForm } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    configs: Record<string, ConfigItem>;
    coinRemitterNetworks: string[];
    paymentoWebhookUrl: string;
};

const ConfigIndex = ({ configs, coinRemitterNetworks, paymentoWebhookUrl }: Props) => {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        configs: Object.fromEntries(
            Object.entries(configs).map(([key, item]) => [key, item.value]),
        ),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(config_update().url);
    };

    const setConfigValue = (key: _ConfigName, value: string) => {
        setData('configs', {
            ...data.configs,
            [key]: value,
        });
    };

    const renderInput = (
        key: _ConfigName,
        defaultLabel: string,
        placeholder: string,
        type: React.HTMLInputTypeAttribute = 'text',
    ) => (
        <div className="space-y-2">
            <Label htmlFor={key}>
                {t(configNameLabel[key], { defaultValue: defaultLabel })}
            </Label>
            <Input
                id={key}
                type={type}
                value={data.configs[key] || ''}
                onChange={(e) => setConfigValue(key, e.target.value)}
                placeholder={placeholder}
            />
            <InputError message={errors[`configs.${key}`]} />
        </div>
    );

    const hasCoinRemitterNetwork = (network: string) =>
        coinRemitterNetworks.includes(network);
    const depositMethod = ['manual', 'coinremitter', 'paymento'].includes(
        data.configs[_ConfigName.CRYPTO_DEPOSIT_METHOD],
    )
        ? data.configs[_ConfigName.CRYPTO_DEPOSIT_METHOD]
        : 'manual';

    return (
        <div>
            <h1 className="text-xl font-semibold">
                {t('menu.crypto_wallet_config', {
                    defaultValue: 'Cấu hình ví Crypto',
                })}
            </h1>
            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>
                        {t('config.title', {
                            defaultValue: 'Cấu hình địa chỉ ví',
                        })}
                    </CardTitle>
                    <CardDescription>
                        {t('config.description', {
                            defaultValue:
                                'Cấu hình địa chỉ ví để nhận tiền từ giao dịch crypto',
                        })}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label
                                    htmlFor={_ConfigName.CRYPTO_DEPOSIT_METHOD}
                                >
                                    {t(
                                        configNameLabel[
                                            _ConfigName.CRYPTO_DEPOSIT_METHOD
                                        ],
                                        {
                                            defaultValue:
                                                'Phương thức nạp crypto',
                                        },
                                    )}
                                </Label>
                                <Select
                                    value={depositMethod}
                                    onValueChange={(value) =>
                                        setConfigValue(
                                            _ConfigName.CRYPTO_DEPOSIT_METHOD,
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id={_ConfigName.CRYPTO_DEPOSIT_METHOD}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="manual">
                                            {t('config.deposit_method_manual', {
                                                defaultValue: 'Ví thủ công',
                                            })}
                                        </SelectItem>
                                        <SelectItem value="coinremitter">
                                            {t(
                                                'config.deposit_method_coinremitter',
                                                {
                                                    defaultValue:
                                                        'CoinRemitter',
                                                },
                                            )}
                                        </SelectItem>
                                        <SelectItem value="paymento">
                                            {t('config.deposit_method_paymento', {
                                                defaultValue: 'Paymento',
                                            })}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={
                                        errors[
                                            `configs.${_ConfigName.CRYPTO_DEPOSIT_METHOD}`
                                        ]
                                    }
                                />
                            </div>

                            {depositMethod === 'manual' && (
                                <>
                                    <div className="space-y-1">
                                        <h2 className="text-base font-medium">
                                            {t('config.manual_wallet_section', {
                                                defaultValue: 'Ví nạp thủ công',
                                            })}
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'config.manual_wallet_section_description',
                                                {
                                                    defaultValue:
                                                        'Khách sẽ tạo lệnh nạp theo địa chỉ ví và admin duyệt thủ công.',
                                                },
                                            )}
                                        </p>
                                    </div>

                                    {renderInput(
                                        _ConfigName.BEP20_WALLET_ADDRESS,
                                        'Địa chỉ ví BEP20',
                                        t('config.bep20_placeholder', {
                                            defaultValue:
                                                'Nhập địa chỉ ví Binance Smart Chain (BEP20)',
                                        }),
                                    )}

                                    {renderInput(
                                        _ConfigName.TRC20_WALLET_ADDRESS,
                                        'Địa chỉ ví TRC20',
                                        t('config.trc20_placeholder', {
                                            defaultValue:
                                                'Nhập địa chỉ ví Tron (TRC20)',
                                        }),
                                    )}
                                </>
                            )}

                            {depositMethod === 'coinremitter' && (
                                <div className="space-y-4 border-t pt-5">
                                    <div className="space-y-1">
                                        <h2 className="text-base font-medium">
                                            {t('config.coinremitter_section', {
                                                defaultValue: 'CoinRemitter',
                                            })}
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'config.coinremitter_section_description',
                                                {
                                                    defaultValue:
                                                        'Cấu hình API ví CoinRemitter để hệ thống tự tạo hóa đơn nạp tiền và nhận webhook.',
                                                },
                                            )}
                                        </p>
                                    </div>

                                    {hasCoinRemitterNetwork('TRC20') && (
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {renderInput(
                                                _ConfigName.COINREMITTER_TRC20_API_KEY,
                                                'TRC20 API key',
                                                'Nhập API key ví TRC20',
                                                'password',
                                            )}
                                            {renderInput(
                                                _ConfigName.COINREMITTER_TRC20_PASSWORD,
                                                'TRC20 API password',
                                                'Nhập API password ví TRC20',
                                                'password',
                                            )}
                                        </div>
                                    )}

                                    {hasCoinRemitterNetwork('BEP20') && (
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {renderInput(
                                                _ConfigName.COINREMITTER_BEP20_API_KEY,
                                                'BEP20 API key',
                                                'Nhập API key ví BEP20',
                                                'password',
                                            )}
                                            {renderInput(
                                                _ConfigName.COINREMITTER_BEP20_PASSWORD,
                                                'BEP20 API password',
                                                'Nhập API password ví BEP20',
                                                'password',
                                            )}
                                        </div>
                                    )}

                                    {coinRemitterNetworks.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'config.coinremitter_no_networks',
                                                {
                                                    defaultValue:
                                                        'Chưa có mạng CoinRemitter nào được bật trong .env.',
                                                },
                                            )}
                                        </p>
                                    )}
                                </div>
                            )}

                            {depositMethod === 'paymento' && (
                                <div className="space-y-4 border-t pt-5">
                                    <div className="space-y-1">
                                        <h2 className="text-base font-medium">
                                            {t('config.paymento_section', {
                                                defaultValue: 'Paymento',
                                            })}
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'config.paymento_section_description',
                                                {
                                                    defaultValue:
                                                        'Cấu hình API Paymento để tạo payment gateway và nhận IPN callback tự động.',
                                                },
                                            )}
                                        </p>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        {renderInput(
                                            _ConfigName.PAYMENTO_API_KEY,
                                            'Paymento API key',
                                            'Nhập Merchant API key',
                                            'password',
                                        )}
                                        {renderInput(
                                            _ConfigName.PAYMENTO_SECRET_KEY,
                                            'Paymento secret key',
                                            'Nhập secret key dùng ký webhook',
                                            'password',
                                        )}
                                    </div>

                                    <div className="rounded-md border bg-muted/40 p-3 text-sm">
                                        <div className="font-medium">
                                            {t('config.paymento_ipn_url', {
                                                defaultValue: 'Paymento IPN URL',
                                            })}
                                        </div>
                                        <div className="mt-1 break-all font-mono">
                                            {paymentoWebhookUrl}
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="space-y-4 border-t pt-5">
                                <div className="space-y-1">
                                    <h2 className="text-base font-medium">
                                        {t('config.wallet_rules_section', {
                                            defaultValue: 'Quy tắc ví',
                                        })}
                                    </h2>
                                </div>

                                {renderInput(
                                    _ConfigName.POSTPAY_MIN_BALANCE,
                                    'Số dư tối thiểu để đăng ký thanh toán trả sau',
                                    t(
                                        'config.postpay_min_balance_placeholder',
                                        {
                                            defaultValue:
                                                'Nhập số dư tối thiểu để đăng ký thanh toán trả sau',
                                        },
                                    ),
                                )}

                                {renderInput(
                                    _ConfigName.THRESHOLD_PAUSE,
                                    'Ngưỡng cảnh báo (USD) để tạm dừng tài khoản',
                                    t('config.threshold_pause_placeholder', {
                                        defaultValue:
                                            'Nhập ngưỡng cảnh báo (USD) để tạm dừng tài khoản',
                                    }),
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? t('common.saving', {
                                          defaultValue: 'Đang lưu...',
                                      })
                                    : t('common.save_changes', {
                                          defaultValue: 'Lưu thay đổi',
                                      })}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
};

ConfigIndex.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.crypto_wallet_config' }]}
        children={page}
    />
);

export default ConfigIndex;
