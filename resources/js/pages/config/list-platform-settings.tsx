import { ReactNode, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import { usePlatformColumns } from '@/pages/config/hooks/use-platform-columns';
import { PlatformSettingListPagination, PlatformSetting } from '@/lib/types/type';
import { usePlatformForm } from '@/pages/config/hooks/use-platform-form';
import PlatformSettingForm, { FieldConfig } from '@/pages/config/components/PlatformSettingForm';
import { Button } from '@/components/ui/button';

type Props = {
  paginator: PlatformSettingListPagination;
  googleFields: FieldConfig[];
  metaFields: FieldConfig[];
};

const ListPlatformSettings = ({ paginator, googleFields, metaFields }: Props) => {
  const { t } = useTranslation();
  const [editingItem, setEditingItem] = useState<PlatformSetting | null>(null);
  
  const { form } = usePlatformForm({
    initial: undefined,
    storeUrl: '/platform-settings',
  });
  const { data, setData, processing, errors, reset, post, put } = form;

  const handleEdit = (item: PlatformSetting) => {
    setEditingItem(item);
    setData({
      platform: item.platform,
      config: item.config || {},
      disabled: item.disabled || false,
    });
  };

  const handleCancelEdit = () => {
    setEditingItem(null);
    reset();
    setData({
      platform: 1,
      config: {},
      disabled: false,
    });
  };

  const handleFormSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (editingItem) {
      put(`/platform-settings/${editingItem.id}`, {
        onSuccess: () => {
          handleCancelEdit();
        },
      });
    } else {
      post('/platform-settings', {
        onSuccess: () => {
          reset();
          setData({
            platform: 1,
            config: {},
            disabled: false,
          });
        },
      });
    }
  };

  const columns = usePlatformColumns({ onEdit: handleEdit });

  return (
    <div>
      <h1 className="text-xl font-semibold">{t('menu.platform_settings', { defaultValue: 'Cấu hình nền tảng' })}</h1>
      <div className="mt-4">
        {editingItem && (
          <div className="mb-4 flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
              {t('platform.edit_mode', { id: editingItem.id })}
            </p>
            <Button type="button" variant="outline" onClick={handleCancelEdit}>
              {t('common.cancel')}
            </Button>
          </div>
        )}
        <PlatformSettingForm
          data={data}
          setData={setData}
          processing={processing}
          errors={errors}
          onSubmit={handleFormSubmit}
          googleFields={googleFields}
          metaFields={metaFields}
        />
      </div>

      <Separator className="my-4" />

      <DataTable columns={columns} paginator={paginator} />
    </div>
  );
};

ListPlatformSettings.layout = (page: ReactNode) => (
  <AppLayout breadcrumbs={[{ title: 'menu.platform_settings' }]} children={page} />
);

export default ListPlatformSettings;


