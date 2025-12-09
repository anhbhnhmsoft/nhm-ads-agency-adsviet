import { ReactNode, useState, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { usePlatformForm } from '@/pages/config/hooks/use-platform-form';
import PlatformSettingForm, { FieldConfig } from '@/pages/config/components/PlatformSettingForm';
import axios from 'axios';
import { _PlatformType } from '@/lib/types/constants';

type Props = {
  googleFields: FieldConfig[];
  metaFields: FieldConfig[];
};

const ListPlatformSettings = ({ googleFields, metaFields }: Props) => {
  const { t } = useTranslation();
  const [currentSetting, setCurrentSetting] = useState<{ id: string; platform: number; config: Record<string, any>; disabled: boolean } | null>(null);
  const [loading, setLoading] = useState(false);
  
  const { form } = usePlatformForm({
    initial: {
      platform: _PlatformType.GOOGLE,
      config: {},
      disabled: false,
    },
    storeUrl: '/platform-settings',
  });
  const { data, setData, processing, errors, reset, post, put } = form;

  useEffect(() => {
    if (data.platform) {
      loadPlatformData(data.platform);
    }
  }, []);

  // Handler khi platform thay đổi
  const handlePlatformChange = (platform: number) => {
    loadPlatformData(platform);
  };

  type PlatformSettingPayload = {
    id: string;
    platform: number;
    config: Record<string, any> | null;
    disabled: boolean;
  };

  const loadPlatformData = async (platform: number) => {
    setLoading(true);
    try {
      const response = await axios.get(`/platform-settings/platform/${platform}`);
      const setting: PlatformSettingPayload | null = response.data?.data ?? null;
      if (setting) {
        setCurrentSetting({
          id: setting.id,
          platform: setting.platform,
          config: setting.config || {},
          disabled: setting.disabled || false,
        });
        setData({
          platform: Number(setting.platform),
          config: (setting.config ?? {}) as Record<string, any>,
          disabled: Boolean(setting.disabled),
        });
      } else {
        setCurrentSetting(null);
        setData({
          platform,
          config: {},
          disabled: false,
        });
      }
    } catch (error) {
      // Chưa có data
      setCurrentSetting(null);
      setData({
        platform,
        config: {},
        disabled: false,
      });
    } finally {
      setLoading(false);
    }
  };

  const handleFormSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (currentSetting) {
      put(`/platform-settings/${currentSetting.id}`, {
        onSuccess: () => {
          loadPlatformData(data.platform);
        },
      });
    } else {
      // Chưa có data, dùng POST để tạo mới
      post('/platform-settings', {
        onSuccess: () => {
          // Reload lại data sau khi tạo
          loadPlatformData(data.platform);
        },
      });
    }
  };

  return (
    <div>
      <h1 className="text-xl font-semibold">{t('menu.platform_settings', { defaultValue: 'Cấu hình nền tảng' })}</h1>
      <Card className="mt-4">
        <CardHeader>
          <CardTitle>{t('platform.title', { defaultValue: 'Cấu hình nền tảng' })}</CardTitle>
          <CardDescription>
            {t('platform.description', { defaultValue: 'Chọn nền tảng và cấu hình thông tin' })}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">{t('common.loading', { defaultValue: 'Đang tải...' })}</div>
          ) : (
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
          )}
        </CardContent>
      </Card>
      <div className="mt-4 flex flex-wrap gap-3">
        <Button asChild variant="outline">
          <a
            href="https://ads.google.com/aw/billing/home"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t('platform.open_google_billing', { defaultValue: 'Mở Billing Google Ads' })}
          </a>
        </Button>
        <Button asChild variant="outline">
          <a
            href="https://business.facebook.com/billing_hub/payment_settings"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t('platform.open_meta_billing', { defaultValue: 'Mở Billing Meta Ads' })}
          </a>
        </Button>
      </div>
    </div>
  );
};

ListPlatformSettings.layout = (page: ReactNode) => (
  <AppLayout breadcrumbs={[{ title: 'menu.platform_settings' }]} children={page} />
);

export default ListPlatformSettings;


