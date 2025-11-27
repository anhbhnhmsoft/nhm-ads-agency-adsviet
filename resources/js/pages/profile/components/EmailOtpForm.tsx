import { FormEvent, MouseEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { useForm } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { profile_verify_email_otp } from '@/routes';
import type { ProfileUser } from '../types/type';

type Props = {
    user: ProfileUser;
    onVerified: () => void;
};

const EmailOtpForm = ({ user, onVerified }: Props) => {
    const { t } = useTranslation();

    const otpForm = useForm({
        otp: '',
    });

    const handleVerifyOtp = (event?: FormEvent<HTMLFormElement> | MouseEvent<HTMLButtonElement>) => {
        if (event) {
            event.preventDefault();
        }

        // Validate client-side
        if (!otpForm.data.otp || otpForm.data.otp.length !== 6 || !/^\d{6}$/.test(otpForm.data.otp)) {
            otpForm.setError('otp', t('profile.otp_invalid'));
            return;
        }

        otpForm.post(profile_verify_email_otp().url, {
            preserveScroll: true,
            onSuccess: () => {
                onVerified();
                otpForm.setData('otp', '');
                otpForm.clearErrors();
            },
            onError: (errors) => {
                if (errors.otp) {
                    otpForm.setError('otp', errors.otp);
                }
            },
        });
    };

    return (
        <div className="mt-6 space-y-2 rounded-lg border p-4">
            <Label htmlFor="otp">{t('profile.enter_otp')}</Label>
            <p className="text-xs text-muted-foreground mb-2">
                {t('profile.otp_sent_hint', { email: user.email })}
            </p>
            <div className="flex gap-2">
                <Input
                    id="otp"
                    type="text"
                    maxLength={6}
                    value={otpForm.data.otp}
                    onChange={(event) => otpForm.setData('otp', event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            handleVerifyOtp(event as any);
                        }
                    }}
                    placeholder={t('profile.otp_placeholder')}
                    disabled={otpForm.processing}
                    aria-invalid={!!otpForm.errors.otp}
                    className="flex-1"
                />
                <Button
                    type="button"
                    size="sm"
                    onClick={handleVerifyOtp}
                    disabled={otpForm.processing || !otpForm.data.otp || otpForm.data.otp.length !== 6}
                >
                    {otpForm.processing ? t('common.processing') : t('profile.verify_button')}
                </Button>
            </div>
            {otpForm.errors.otp && (
                <p className="text-xs text-destructive mt-1">{otpForm.errors.otp}</p>
            )}
            {otpForm.hasErrors && Object.keys(otpForm.errors).length > 0 && (
                <p className="text-xs text-destructive mt-1">
                    {Object.values(otpForm.errors)[0]}
                </p>
            )}
        </div>
    );
};

export default EmailOtpForm;

