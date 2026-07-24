import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    email: string;
};

const ForgotPasswordOtp = ({ email }: Props) => {
    const { t } = useTranslation();
    const form = useForm({ otp: '' });
    const { data, setData, processing, errors } = form;

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.post('/forgot-password/verify-otp');
    };

    return (
        <>
            <Head title={t('auth.forgot_password.verify_otp_title')} />

            <p className="text-sm text-muted-foreground">
                {t('auth.forgot_password.otp_sent_hint', { email })}
            </p>

            <form onSubmit={handleSubmit} className="mt-4 grid gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="otp">{t('auth.forgot_password.enter_otp')}</Label>
                    <div className="relative">
                        <KeyRound className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            id="otp"
                            type="text"
                            value={data.otp}
                            onChange={(e) => setData('otp', e.target.value.replace(/\D/g, '').slice(0, 6))}
                            required
                            autoFocus
                            maxLength={6}
                            placeholder="000000"
                            className="pl-10 tracking-[0.5em] text-center"
                        />
                    </div>
                    <InputError message={errors.otp} />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing && <Spinner />}
                    {t('auth.forgot_password.verify_otp')}
                </Button>
            </form>
        </>
    );
};

ForgotPasswordOtp.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.forgot_password.verify_otp_title"
    />
);

export default ForgotPasswordOtp;
