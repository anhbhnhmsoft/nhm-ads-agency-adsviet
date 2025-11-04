import { useForm } from '@inertiajs/react';
import { _UserRole } from '@/lib/types/constants';
import { Employee } from '@/pages/user/types/type';

type UseEntityFormParams = {
  initial?: Employee;
  defaultRole: _UserRole | number;
  storeUrl?: string;
  updateUrl?: string;
  lockUsernameOnEdit?: boolean;
};

export const useEntityForm = ({ initial, defaultRole, storeUrl, updateUrl }: UseEntityFormParams) => {
  const isEdit = !!initial?.id;
  const form = useForm({
    name: initial?.name || '',
    username: initial?.username || '',
    password: '',
    phone: initial?.phone || '',
    role: initial?.role ?? defaultRole,
    disabled: initial?.disabled ?? false,
  });

  const handleSubmitForm = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (isEdit && initial?.id && updateUrl) {
      form.put(updateUrl);
      return;
    }
    if (!isEdit && storeUrl) {
      form.post(storeUrl);
    }
  };

  return { form, handleSubmitForm, isEdit };
};


