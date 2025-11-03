import { ReactNode } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { useEmployeeCreateForm } from '@/pages/user/hooks/use-form';
import { _UserRole } from '@/lib/types/constants';
import { Link, usePage } from '@inertiajs/react';
import { user_list_employee } from '@/routes';
import { Employee } from '@/pages/user/types/type';
import useCheckRole from '@/hooks/use-check-role';

type Props = {
  employee?: Employee;
};

const CreateEmployee = ({ employee }: Props) => {
  const { t } = useTranslation();
  const { props } = usePage();
  const checkRole = useCheckRole(props.auth as any);
  const isAdmin = checkRole([_UserRole.ADMIN]);
  const isManager = checkRole([_UserRole.MANAGER]);
  const { form, handleSubmitForm, isEdit } = useEmployeeCreateForm(employee);
  const { data, setData, processing, errors } = form;

  const roleOptions = [
    { value: _UserRole.MANAGER, label: t('enum.user_role.manager') },
    { value: _UserRole.EMPLOYEE, label: t('enum.user_role.employee') },
  ];

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">
        {isEdit 
          ? t('user.edit_employee', { defaultValue: 'Chỉnh sửa nhân viên' })
          : t('menu.user_create_employee', { defaultValue: 'Tạo nhân viên' })
        }
      </h1>

      <form
        className="grid grid-cols-1 md:grid-cols-2 gap-4"
        onSubmit={handleSubmitForm}
      >
        <div className="grid gap-1">
          <label>{t('common.name', { defaultValue: 'Họ tên' })}</label>
          <Input
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
            required
          />
          {errors.name && <span className="text-red-500 text-sm">{errors.name}</span>}
        </div>

        <div className="grid gap-1">
          <label>{t('common.username', { defaultValue: 'Tên đăng nhập' })}</label>
          <Input
            value={data.username}
            onChange={(e) => setData('username', e.target.value)}
            required
            disabled={isEdit}
          />
          {errors.username && <span className="text-red-500 text-sm">{errors.username}</span>}
        </div>

        <div className="grid gap-1">
          <label>
            {t('common.password', { defaultValue: 'Mật khẩu' })}
            {isEdit && <span className="text-gray-500 text-sm ml-1">(Để trống nếu không đổi)</span>}
          </label>
          <Input
            type="password"
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            required={!isEdit}
            placeholder={isEdit ? t('common.password_placeholder', { defaultValue: 'Nhập mật khẩu mới (nếu muốn đổi)' }) : ''}
          />
          {errors.password && <span className="text-red-500 text-sm">{errors.password}</span>}
        </div>

        <div className="grid gap-1">
          <label>{t('common.phone', { defaultValue: 'Số điện thoại' })}</label>
          <Input
            value={data.phone}
            onChange={(e) => setData('phone', e.target.value)}
          />
        </div>

        <div className="grid gap-1">
          <label>{t('common.role', { defaultValue: 'Vai trò' })}</label>
          <select
            className="border rounded-md h-9 px-3"
            value={data.role}
            onChange={(e) => setData('role', Number(e.target.value))}
            required
            disabled={isEdit && isManager && !isAdmin}
          >
            {roleOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          {isEdit && isManager && !isAdmin && (
            <span className="text-sm text-gray-500">{t('user.role_edit_disabled', { defaultValue: 'Manager không được phép chỉnh sửa vai trò' })}</span>
          )}
        </div>

        <div className="hidden md:block" />

        <div className="flex items-center gap-2 col-span-full">
          <input
            id="disabled"
            type="checkbox"
            checked={!!data.disabled}
            onChange={(e) => setData('disabled', e.target.checked)}
          />
          <label htmlFor="disabled">
            {t('common.disabled', { defaultValue: 'Vô hiệu' })}
          </label>
        </div>

        <div className="col-span-full flex items-center gap-2">
          <Button type="submit" disabled={processing}>
            {t('common.save', { defaultValue: 'Lưu' })}
          </Button>
          <Button asChild variant="outline">
            <Link href={user_list_employee().url}>
              {t('common.back', { defaultValue: 'Quay lại' })}
            </Link>
          </Button>
        </div>
      </form>
    </div>
  );
};

CreateEmployee.layout = (page: ReactNode) => (
  <AppLayout breadcrumbs={[{ title: 'menu.user_list_employee' }]} children={page} />
);

export default CreateEmployee;