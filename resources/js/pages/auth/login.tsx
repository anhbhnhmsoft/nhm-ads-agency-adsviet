import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import RoleSystemCard from '@/pages/auth/components/RoleSystemCard';
import { useFormLogin } from '@/pages/auth/hooks/use-form';
import { register } from '@/routes';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

const Login = () => {
    const { t } = useTranslation();

    const { form, handleSubmit } = useFormLogin();

    const { data, setData, processing, errors } = form;

    return (
        <>
            <Head title={t('auth.login.title')} />

            <div className={'flex flex-col gap-2'}>
                <RoleSystemCard role={data.role} setRole={(role) => setData('role', role)} />
                <InputError message={errors.role} />

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('common.username')}</Label>
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
                        <Input
                            id="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            type="password"
                            name="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder={t('common.password')}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <Button
                        onClick={handleSubmit}
                        className="mt-4 w-full"
                        tabIndex={4}
                        disabled={processing}

                    >
                        {processing && <Spinner />}
                        {t('auth.login.login')}
                    </Button>
                </div>

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
                <Button
                    variant={'outline'}
                    className={
                        'bg-blue-500 text-white hover:bg-blue-600 hover:text-white'
                    }
                >
                    <svg
                        viewBox="0 0 24 24"
                        className="text-white"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M23.1117 4.49449C23.4296 2.94472 21.9074 1.65683 20.4317 2.227L2.3425 9.21601C0.694517 9.85273 0.621087 12.1572 2.22518 12.8975L6.1645 14.7157L8.03849 21.2746C8.13583 21.6153 8.40618 21.8791 8.74917 21.968C9.09216 22.0568 9.45658 21.9576 9.70712 21.707L12.5938 18.8203L16.6375 21.8531C17.8113 22.7334 19.5019 22.0922 19.7967 20.6549L23.1117 4.49449ZM3.0633 11.0816L21.1525 4.0926L17.8375 20.2531L13.1 16.6999C12.7019 16.4013 12.1448 16.4409 11.7929 16.7928L10.5565 18.0292L10.928 15.9861L18.2071 8.70703C18.5614 8.35278 18.5988 7.79106 18.2947 7.39293C17.9906 6.99479 17.4389 6.88312 17.0039 7.13168L6.95124 12.876L3.0633 11.0816ZM8.17695 14.4791L8.78333 16.6015L9.01614 15.321C9.05253 15.1209 9.14908 14.9366 9.29291 14.7928L11.5128 12.573L8.17695 14.4791Z"
                        />
                    </svg>
                    {t('auth.login.login_with', { provider: 'Telegram' })}
                </Button>

                <Button
                    variant={'outline'}
                    className={
                        'bg-green-600 text-white hover:bg-green-700 hover:text-white'
                    }
                >
                    <svg
                        fill="currentColor"
                        className="text-white"
                        viewBox="0 0 32 32"
                        version="1.1"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <title>whatsapp</title>
                        <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path>
                    </svg>
                    {t('auth.login.login_with', { provider: 'WhatsApp' })}
                </Button>
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
