import { ReactNode } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { DataTable } from '@/components/table/data-table';
import { Separator } from '@/components/ui/separator';
import { usePlatformColumns } from '@/pages/config/hooks/use-platform-columns';
import { PlatformSettingListPagination } from '@/lib/types/type';
import { usePlatformForm } from '@/pages/config/hooks/use-platform-form';
import PlatformSettingForm, { FieldConfig } from '@/pages/config/components/PlatformSettingForm';

type Props = {
  paginator: PlatformSettingListPagination;
  googleFields: FieldConfig[];
  metaFields: FieldConfig[];
};

const ListPlatformSettings = ({ paginator, googleFields, metaFields }: Props) => {
  const { t } = useTranslation();
  const columns = usePlatformColumns();
  const { form, handleSubmit } = usePlatformForm({
    initial: undefined,
    storeUrl: '/platform-settings',
  });
  const { data, setData, processing, errors } = form;

  return (
    <div>
      <h1 className="text-xl font-semibold">{t('menu.platform_settings', { defaultValue: 'Cấu hình nền tảng' })}</h1>
      <div className="mt-4">
        <PlatformSettingForm
          data={data}
          setData={setData}
          processing={processing}
          errors={errors}
          onSubmit={handleSubmit}
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


