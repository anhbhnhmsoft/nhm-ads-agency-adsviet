import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

type RoleOption = { value: number; label: string };

type Props = {
  data: {
    name: string;
    username: string;
    password: string;
    phone?: string | null;
    role: number;
    disabled: boolean;
  };
  errors: Record<string, string>;
  processing: boolean;
  setData: (key: string, value: any) => void;
  isEdit: boolean;
  roleOptions: RoleOption[];
  canEditRole: boolean;
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
  backHref: string;
  title: string;
};

export default function UserForm({ data, errors, processing, setData, isEdit, roleOptions, canEditRole, onSubmit, backHref, title }: Props) {
  const { t } = useTranslation();
  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">{title}</h1>
      <form className="grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={onSubmit}>
        <div className="grid gap-1">
          <label>{t('common.name')}</label>
          <Input value={data.name} onChange={(e) => setData('name', e.target.value)} required />
          {errors.name && <span className="text-red-500 text-sm">{errors.name}</span>}
        </div>

        <div className="grid gap-1">
          <label>{t('common.username')}</label>
          <Input value={data.username} onChange={(e) => setData('username', e.target.value)} required disabled={isEdit} />
          {errors.username && <span className="text-red-500 text-sm">{errors.username}</span>}
        </div>

        <div className="grid gap-1">
          <label>
            {t('common.password')}
            {isEdit && <span className="text-gray-500 text-sm ml-1">{t('common.password_optional')}</span>}
          </label>
          <Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required={!isEdit} placeholder={isEdit ? t('common.password_placeholder') : ''} />
          {errors.password && <span className="text-red-500 text-sm">{errors.password}</span>}
        </div>

        <div className="grid gap-1">
          <label>{t('common.phone')}</label>
          <Input value={data.phone || ''} onChange={(e) => setData('phone', e.target.value)} />
        </div>

        <div className="grid gap-1">
          <label>{t('common.role')}</label>
          <select className="border rounded-md h-9 px-3" value={data.role} onChange={(e) => setData('role', Number(e.target.value))} required disabled={!canEditRole}>
            {roleOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          {!canEditRole && isEdit && <span className="text-sm text-gray-500">{t('user.role_edit_disabled')}</span>}
        </div>

        <div className="hidden md:block" />

        <div className="flex items-center gap-2 col-span-full">
          <input id="disabled" type="checkbox" checked={!!data.disabled} onChange={(e) => setData('disabled', e.target.checked)} />
          <label htmlFor="disabled">{t('common.disabled')}</label>
        </div>

        <div className="col-span-full flex items-center gap-2">
          <Button type="submit" disabled={processing}>
            {t('common.save')}
          </Button>
          <Button asChild variant="outline">
            <a href={backHref}>{t('common.back')}</a>
          </Button>
        </div>
      </form>
    </div>
  );
}


