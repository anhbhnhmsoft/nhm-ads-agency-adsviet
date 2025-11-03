import { useForm } from '@inertiajs/react';
import { _UserRole } from '@/lib/types/constants';
import { user_employee_store, user_employee_update } from '@/routes';
import { EmployeeFormData } from '@/pages/user/types/type';

export const useEmployeeCreateForm = (employee?: EmployeeFormData) => {
  const isEdit = !!employee?.id;
  
  const form = useForm({
    name: employee?.name || '',
    username: employee?.username || '',
    password: '',
    phone: employee?.phone || '',
    role: employee?.role || _UserRole.EMPLOYEE,
    disabled: employee?.disabled || false,
  });

  const handleSubmitForm = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (isEdit && employee?.id) {
      form.put(user_employee_update({ id: employee.id }).url);
    } else {
      form.post(user_employee_store().url);
    }
  };

  return { form, handleSubmitForm, isEdit };
};
