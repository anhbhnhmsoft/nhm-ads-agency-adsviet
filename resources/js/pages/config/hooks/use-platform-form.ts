import { useForm } from '@inertiajs/react';

type Props = {
  initial?: { platform?: number; config?: Record<string, any>; disabled?: boolean };
  storeUrl?: string;
  updateUrl?: string;
};

export function usePlatformForm({ initial, storeUrl, updateUrl }: Props) {
  const isEdit = !!updateUrl;
  const form = useForm({
    platform: initial?.platform ?? 1,
    config: initial?.config ?? {},
    disabled: initial?.disabled ?? false,
  });

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (isEdit && updateUrl) return form.put(updateUrl);
    if (storeUrl) return form.post(storeUrl);
  };

  return { form, handleSubmit, isEdit };
}


