import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { Head, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

const ForgotPassword = () => {
    const { t } = useTranslation();
    const form = useForm({ email: '' });
    const { data, setData, processing, errors } = form;

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.post('/forgot-password');
    };

    return (
        <>
            <Head title={t('auth.forgot_password.title', { defaultValue: 'Quên mật khẩu' })} />

            <div className="flex flex-col gap-2">
                <p className="text-sm text-muted-foreground">
                    {t('auth.forgot_password.description', {
                        defaultValue: 'Nhập email đã đăng ký để nhận mã OTP xác minh.',
                    })}
                </p>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('common.email', { defaultValue: 'Email' })}</Label>
                        <div className="relative">
                            <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoFocus
                                placeholder={t('auth.forgot_password.email_placeholder', { defaultValue: 'email@example.com' })}
                                className="pl-10"
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing && <Spinner />}
                        {t('auth.forgot_password.send_otp', { defaultValue: 'Gửi mã OTP' })}
                    </Button>
                </form>

                <div className="text-center text-sm text-muted-foreground">
                    {t('auth.forgot_password.back_to_login', { defaultValue: 'Quay lại' })}{' '}
                    <TextLink href={login()}>{t('auth.login.title')}</TextLink>
                </div>
            </div>
        </>
    );
};

ForgotPassword.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.forgot_password.title"
        description="auth.forgot_password.description"
    />
);

export default ForgotPassword;
