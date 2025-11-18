import { useMemo } from 'react';
import { ColumnDef } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { PlatformSetting } from '@/lib/types/type';
import { platformTypeLabel } from '@/lib/types/constants';
import { Check, OctagonX, Pencil } from 'lucide-react';

type UsePlatformColumnsProps = {
  onEdit?: (item: PlatformSetting) => void;
};

export function usePlatformColumns({ onEdit }: UsePlatformColumnsProps = {}) {
  const { t } = useTranslation();

  return useMemo<ColumnDef<PlatformSetting>[]>(() => [
    { accessorKey: 'id', header: t('common.id') },
    {
      accessorKey: 'platform',
      header: t('common.foundation'),
      cell: (cell) => t(platformTypeLabel[cell.row.original.platform]),
    },
    {
      id: 'app_id_and_client_id',
      header: t('platform.app_or_client_id'),
      cell: ({ row }) => {
        const platform = row.original.platform;
        const cfg = row.original.config || {};
        const value = platform === 2 ? cfg.app_id : cfg.client_id;
        return <div className="text-sm">{value || '-'}</div>;
      },
    },
    {
      id: 'bm_id_and_lc_id',
      header: t('platform.bm_or_login_id'),
      cell: ({ row }) => {
        const platform = row.original.platform;
        const cfg = row.original.config || {};
        const value = platform === 2 ? cfg.business_manager_id : cfg.login_customer_id;
        return <div className="text-sm">{value || '-'}</div>;
      },
    },
    {
      accessorKey: 'disabled',
      header: t('common.account_active'),
      cell: (cell) => {
        const disabled = cell.row.original.disabled;
        return (
          <div className="flex items-center justify-center">
            {!disabled ? (
              <Check className="size-4 text-green-500" />
            ) : (
              <OctagonX className="size-4 text-red-500" />
            )}
          </div>
        );
      },
      meta: { headerClassName: 'text-center' },
    },
    {
      id: 'action',
      header: t('common.action'),
      cell: ({ row }) => {
        const it = row.original;
        const disabled = !!it.disabled;
        return (
          <div className="flex items-center justify-center gap-2">
            {onEdit && (
              <button
                type="button"
                className="h-8 px-3 rounded border flex items-center gap-1 hover:bg-gray-100"
                onClick={() => onEdit(it)}
              >
                <Pencil className="h-3 w-3" />
                {t('common.edit')}
              </button>
            )}
            <button
              type="button"
              className={`h-8 px-3 rounded border ${disabled ? 'bg-primary text-white' : ''}`}
              onClick={() =>
                router.post(
                  `/platform-settings/${it.id}/toggle`,
                  { disabled: !disabled },
                  { preserveScroll: true }
                )
              }
            >
              {disabled ? t('common.active') : t('common.disabled')}
            </button>
          </div>
        );
      },
      meta: { headerClassName: 'text-center', cellClassName: 'text-center' },
    },
  ], [t, onEdit]);
}


