import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { Lock, Eye, EyeOff } from 'lucide-react';
import { ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

const ForgotPasswordReset = () => {
    const { t } = useTranslation();
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const form = useForm({ password: '', password_confirmation: '' });
    const { data, setData, processing, errors } = form;

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        form.post('/forgot-password/reset');
    };

    return (
        <>
            <Head title={t('auth.forgot_password.reset_title', { defaultValue: 'Đặt lại mật khẩu' })} />

            <div className="flex flex-col gap-2">
                <p className="text-sm text-muted-foreground">
                    {t('auth.forgot_password.reset_description', {
                        defaultValue: 'Nhập mật khẩu mới cho tài khoản của bạn.',
                    })}
                </p>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="password">{t('common.password', { defaultValue: 'Mật khẩu mới' })}</Label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                autoFocus
                                minLength={6}
                                placeholder={t('auth.forgot_password.new_password_placeholder', { defaultValue: 'Tối thiểu 6 ký tự' })}
                                className="pl-10 pr-10"
                            />
                            <button
                                type="button"
                                tabIndex={-1}
                                className="absolute top-1/2 right-3 flex -translate-y-1/2 text-gray-400 hover:text-gray-700"
                                onClick={() => setShowPassword((prev) => !prev)}
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">{t('common.password_confirmation', { defaultValue: 'Xác nhận mật khẩu' })}</Label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                id="password_confirmation"
                                type={showConfirm ? 'text' : 'password'}
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                                minLength={6}
                                placeholder={t('common.password_confirmation', { defaultValue: 'Nhập lại mật khẩu' })}
                                className="pl-10 pr-10"
                            />
                            <button
                                type="button"
                                tabIndex={-1}
                                className="absolute top-1/2 right-3 flex -translate-y-1/2 text-gray-400 hover:text-gray-700"
                                onClick={() => setShowConfirm((prev) => !prev)}
                            >
                                {showConfirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing && <Spinner />}
                        {t('auth.forgot_password.reset_button', { defaultValue: 'Đặt lại mật khẩu' })}
                    </Button>
                </form>
            </div>
        </>
    );
};

ForgotPasswordReset.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.forgot_password.reset_title"
        description="auth.forgot_password.description"
    />
);

export default ForgotPasswordReset;
