import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import TelegramButton from '@/pages/auth/components/TelegramButton';
import { useFormLogin } from '@/pages/auth/hooks/use-form';
import { register } from '@/routes';
import { Head } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    bot_username: string;
};

const Login = ({ bot_username }: Props) => {
    const { t } = useTranslation();
    const { form, handleSubmit } = useFormLogin();
    const { data, setData, processing, errors } = form;

    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <Head title={t('auth.login.title')} />

            <div className={'flex flex-col gap-2'}>
                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="username">{t('common.username')}</Label>
                        <Input
                            id="username"
                            value={data.username}
                            onChange={(e) =>
                                setData('username', e.target.value)
                            }
                            type="text"
                            name="username"
                            autoComplete={'username'}
                            required
                            autoFocus
                            tabIndex={1}
                            placeholder={t('common.username')}
                        />
                        <InputError message={errors.username} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">
                                {t('common.password')}
                            </Label>
                        </div>
                        <div className="relative">
                            <Input
                                id="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder={t('common.password')}
                            />
                            <button
                                type="button"
                                tabIndex={-1}
                                className="absolute top-1/2 right-3 flex -translate-y-1/2 items-center text-gray-400 hover:text-gray-700"
                                onClick={() => setShowPassword((prev) => !prev)}
                                aria-label={
                                    showPassword
                                        ? t('common.hide_password')
                                        : t('common.show_password')
                                }
                            >
                                {showPassword ? (
                                    <EyeOff className="h-5 w-5" />
                                ) : (
                                    <Eye className="h-5 w-5" />
                                )}
                            </button>
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    <Button
                        type="submit"
                        className="mt-4 w-full"
                        tabIndex={4}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {t('auth.login.login')}
                    </Button>
                </form>

                <div className="text-center text-sm text-muted-foreground">
                    {t('auth.login.signup_prompt')}
                    <TextLink href={register()} tabIndex={5}>
                        {t('auth.login.signup_link')}
                    </TextLink>
                </div>
            </div>
            <Separator className={'my-4'} />
            <div className={'flex flex-col gap-6'}>
                <p className="text-center text-sm text-muted-foreground">
                    {t('common.or')}
                </p>
                {/*Login with telegram*/}
                <TelegramButton bot_username={bot_username} />
            </div>
        </>
    );
};

Login.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.login.title"
        description="auth.login.description"
    />
);

export default Login;
