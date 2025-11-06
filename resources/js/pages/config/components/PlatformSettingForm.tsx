import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useMemo } from 'react';

export type FieldConfig = {
  key: string;
  label: string;
  type: 'text' | 'password' | 'textarea' | 'boolean' | 'number';
  required: boolean;
  placeholder?: string;
  description?: string;
  default_value?: string | boolean | number;
};

type Props = {
  data: { platform: number; config: Record<string, any>; disabled: boolean };
  setData: (key: string, value: any) => void;
  processing: boolean;
  errors: Record<string, string>;
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
  googleFields: FieldConfig[];
  metaFields: FieldConfig[];
};

export default function PlatformSettingForm({ data, setData, processing, errors, onSubmit, googleFields, metaFields }: Props) {
  const { t } = useTranslation();
  
  const currentFields = useMemo(() => {
    return data.platform === 1 ? googleFields : metaFields;
  }, [data.platform, googleFields, metaFields]);

  const handleConfigChange = (key: string, value: any) => {
    const newConfig = { ...data.config, [key]: value };
    setData('config', newConfig);
  };

  const renderField = (field: FieldConfig) => {
    const value = data.config[field.key] ?? field.default_value ?? '';
    const fieldId = `config_${field.key}`;

    switch (field.type) {
      case 'password':
      case 'text':
        return (
          <div key={field.key} className="grid gap-1">
            <label htmlFor={fieldId}>
              {field.label}
              {field.required && <span className="text-red-500 ml-1">*</span>}
            </label>
            <Input
              id={fieldId}
              type={field.type}
              value={String(value)}
              onChange={(e) => handleConfigChange(field.key, e.target.value)}
              placeholder={field.placeholder}
              required={field.required}
            />
            {field.description && (
              <span className="text-sm text-gray-500">{field.description}</span>
            )}
            {errors[`config.${field.key}`] && (
              <span className="text-red-500 text-sm">{errors[`config.${field.key}`]}</span>
            )}
          </div>
        );

      case 'textarea':
        return (
          <div key={field.key} className="grid gap-1 col-span-full">
            <label htmlFor={fieldId}>
              {field.label}
              {field.required && <span className="text-red-500 ml-1">*</span>}
            </label>
            <Textarea
              id={fieldId}
              value={Array.isArray(value) ? value.join('\n') : String(value)}
              onChange={(e) => {
                const val = e.target.value;
                // Nếu là array field (như customer_ids, ad_account_ids), split by newline
                if (field.key.includes('_ids') || field.key.includes('ids')) {
                  handleConfigChange(field.key, val.split('\n').filter(Boolean));
                } else {
                  handleConfigChange(field.key, val);
                }
              }}
              placeholder={field.placeholder}
              required={field.required}
              className="min-h-24"
            />
            {field.description && (
              <span className="text-sm text-gray-500">{field.description}</span>
            )}
            {errors[`config.${field.key}`] && (
              <span className="text-red-500 text-sm">{errors[`config.${field.key}`]}</span>
            )}
          </div>
        );

      case 'boolean':
        return (
          <div key={field.key} className="flex items-center gap-2 col-span-full">
            <input
              id={fieldId}
              type="checkbox"
              checked={!!value}
              onChange={(e) => handleConfigChange(field.key, e.target.checked)}
            />
            <label htmlFor={fieldId}>
              {field.label}
              {field.description && (
                <span className="text-sm text-gray-500 ml-2">({field.description})</span>
              )}
            </label>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <form className="grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={onSubmit}>
      <div className="grid gap-1">
        <label>{t('common.foundation')}</label>
        <select
          className="border rounded-md h-9 px-3"
          value={data.platform}
          onChange={(e) => {
            setData('platform', Number(e.target.value));
            // Reset config khi đổi platform
            setData('config', {});
          }}
          required
        >
          <option value={1}>Google Ads</option>
          <option value={2}>Meta Ads</option>
        </select>
      </div>

      <div className="hidden md:block" />

      {currentFields.map(renderField)}

      <div className="flex items-center gap-2 col-span-full">
        <input id="disabled" type="checkbox" checked={!!data.disabled} onChange={(e) => setData('disabled', e.target.checked)} />
        <label htmlFor="disabled">{t('common.disabled')}</label>
      </div>

      <div className="col-span-full flex items-center gap-2">
        <Button type="submit" disabled={processing}>{t('common.save')}</Button>
      </div>
    </form>
  );
}


