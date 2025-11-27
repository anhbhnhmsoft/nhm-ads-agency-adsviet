import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';

export const useEmailOtpSent = () => {
    const { flash } = usePage().props as any;
    const [emailOtpSent, setEmailOtpSent] = useState(() => {
        const stored = sessionStorage.getItem('profile_email_otp_sent');
        return stored === 'true';
    });

    useEffect(() => {
        if (flash?.info && typeof flash.info === 'string' && flash.info.includes('xác minh')) {
            setEmailOtpSent(true);
            sessionStorage.setItem('profile_email_otp_sent', 'true');
        }
    }, [flash]);

    const resetOtpSent = () => {
        setEmailOtpSent(false);
        sessionStorage.removeItem('profile_email_otp_sent');
    };

    return { emailOtpSent, setEmailOtpSent, resetOtpSent };
};

