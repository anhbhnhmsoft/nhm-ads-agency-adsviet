import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

type Props = {
  phone: string;
  onVerified: () => void;
};

export default function WhatsAppOtpVerify({ phone, onVerified }: Props) {
  const [otp, setOtp] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { t } = useTranslation();

  const handleVerify = () => {
    if (!otp) return setError(t('auth.register.otp_required'));
    setError('');
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      onVerified();
    }, 1000);
  };

  return (
    <div className="grid gap-2">
      <label>{t('auth.register.enter_otp')}</label>
      <Input
        type="text"
        value={otp}
        onChange={e => setOtp(e.target.value)}
        placeholder={t('auth.register.enter_otp')}
      />
      <Button onClick={handleVerify} disabled={loading} type="button">
        {loading ? t('auth.register.verifying') : t('auth.register.verify_otp')}
      </Button>
      {!!error && <div className="text-red-500 text-sm">{error}</div>}
    </div>
  );
}
