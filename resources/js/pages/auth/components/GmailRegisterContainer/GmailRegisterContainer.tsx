import { useState } from 'react';
import GmailEmailStep from './GmailEmailStep';
import GmailOtpVerify from './GmailOtpVerify';

export default function GmailRegisterContainer() {
    const [step, setStep] = useState<'email' | 'otp'>('email');
    const [email, setEmail] = useState('');

    if (step === 'email') {
        return (
            <GmailEmailStep
                onSuccess={(value) => {
                    setEmail(value);
                    setStep('otp');
                }}
            />
        );
    }

    return (
        <GmailOtpVerify
            email={email}
            onBack={() => setStep('email')}
        />
    );
}

