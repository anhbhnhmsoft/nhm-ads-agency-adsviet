import { useState } from 'react';
import WhatsAppInputPhone from './WhatsAppInputPhone';
import WhatsAppOtpVerify from './WhatsAppOtpVerify';
import { router } from '@inertiajs/react';

export default function WhatsAppRegisterContainer() {
  const [step, setStep] = useState<'phone'|'otp'>('phone');
  const [phone, setPhone] = useState('');

  if (step === 'phone') {
    return <WhatsAppInputPhone onSuccess={(p) => { setPhone(p); setStep('otp'); }} />;
  }
  if (step === 'otp') {
    return (
      <WhatsAppOtpVerify
        phone={phone}
        onVerified={() => router.visit('/auth/register-new-user')}
      />
    );
  }
  return null;
}