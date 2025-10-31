import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

type Props = {
  onSuccess: (phone: string) => void;
};

export default function WhatsAppInputPhone({ onSuccess }: Props) {
  const [phone, setPhone] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { t } = useTranslation();

  const handleSendOtp = () => {
    if (!phone) return setError(t('auth.register.phone_required'));
    setError('');
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      onSuccess(phone);
    }, 1000);
  };

  return (
    <div className="grid gap-2">
      <label>{t('auth.register.number_whatsapp')}</label>
      <Input
        type="text"
        value={phone}
        onChange={e => setPhone(e.target.value)}
        placeholder={t('auth.register.import_number_whatsapp')}
      />
      <Button onClick={handleSendOtp} disabled={loading} type="button" className='mt-4'>
        {loading ? t('auth.register.sending') : t('auth.register.send_otp')}
      </Button>
      {!!error && <div className="text-red-500 text-sm">{error}</div>}
    </div>
  );
}
