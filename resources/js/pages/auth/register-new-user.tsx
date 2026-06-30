import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import CustomerRoleCard from '@/pages/auth/components/CustomerRoleCard';
import { useFormRegister } from '@/pages/auth/hooks/use-form';
import { TelegramUser } from '@/pages/auth/types/types';
import { Head } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    social_data?: {
        type: 'telegram' | 'gmail';
        data: TelegramUser | { email?: string };
    } | null;
};

const RegisterNewUser = ({ social_data }: Props) => {
    const { t } = useTranslation();

    const { form, handleSubmit } = useFormRegister();

    const { data, setData, processing, errors } = form;
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] =
        useState(false);

    useEffect(() => {
        if (!social_data) return;
        if (social_data.type === 'telegram') {
            const user = social_data.data as TelegramUser;
            setData('type', 'telegram');
            setData(
                'name',
                `${user.first_name} ${user.last_name ?? ''}`.trim(),
            );
        }
        if (social_data.type === 'gmail') {
            const gmailData = social_data.data as { email?: string };
            setData('type', 'gmail');
            setData('email', gmailData.email ?? '');
        }
    }, [social_data, setData]);

    if (!social_data) {
        return (
            <div className="flex min-h-[200px] items-center justify-center">
                <Spinner />
            </div>
        );
    }

    return (
        <div>
            <Head title={t('auth.register_new_user.title')} />

            <div className={'flex flex-col gap-2'}>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            <span className="text-red-500">*</span>
                            {t('common.name')}
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            type="text"
                            name="name"
                            autoComplete={'name'}
                            required
                            autoFocus
                            tabIndex={1}
                            placeholder={t('common.name')}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="username">
                            <span className="text-red-500">*</span>
                            {t('common.username')}
                        </Label>
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

                    {data.type === 'gmail' && (
                        <div className="grid gap-2">
                            <Label htmlFor="email">{t('common.email')}</Label>
                            <Input
                                id="email"
                                value={data.email}
                                readOnly
                                disabled
                                type="email"
                            />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">
                                <span className="text-red-500">*</span>
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
                                autoComplete="new-password"
                                placeholder={t('common.password')}
                                className="pr-10"
                            />
                            <button
                                type="button"
                                tabIndex={-1}
                                className="absolute top-1/2 right-3 flex -translate-y-1/2 items-center text-gray-400 hover:text-gray-700"
                                onClick={() => setShowPassword((prev) => !prev)}
                                aria-label={
                                    showPassword
                                        ? 'Hide password'
                                        : 'Show password'
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

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password_confirmation">
                                <span className="text-red-500">*</span>
                                {t('common.password_confirmation')}
                            </Label>
                        </div>
                        <div className="relative">
                            <Input
                                id="password_confirmation"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                type={
                                    showPasswordConfirmation
                                        ? 'text'
                                        : 'password'
                                }
                                name="password_confirmation"
                                required
                                tabIndex={3}
                                autoComplete="new-password"
                                placeholder={t('common.password_confirmation')}
                                className="pr-10"
                            />
                            <button
                                type="button"
                                tabIndex={-1}
                                className="absolute top-1/2 right-3 flex -translate-y-1/2 items-center text-gray-400 hover:text-gray-700"
                                onClick={() =>
                                    setShowPasswordConfirmation((prev) => !prev)
                                }
                                aria-label={
                                    showPasswordConfirmation
                                        ? 'Hide password'
                                        : 'Show password'
                                }
                            >
                                {showPasswordConfirmation ? (
                                    <EyeOff className="h-5 w-5" />
                                ) : (
                                    <Eye className="h-5 w-5" />
                                )}
                            </button>
                        </div>
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="refer_code">
                            <span className="text-red-500">*</span>
                            {t('common.refer_code')}
                        </Label>
                        <Input
                            id="refer_code"
                            value={data.refer_code}
                            onChange={(e) =>
                                setData('refer_code', e.target.value)
                            }
                            type="text"
                            name="refer_code"
                            required
                            autoFocus
                            tabIndex={1}
                            placeholder={t('common.refer_code')}
                        />
                        <InputError message={errors.refer_code} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="role">
                            <span className="text-red-500">*</span>
                            {t('auth.register_new_user.role')}
                        </Label>
                        <CustomerRoleCard
                            role={data.role}
                            setRole={(role) => setData('role', role)}
                        />
                        <InputError message={errors.role} />
                    </div>

                    <Button
                        onClick={handleSubmit}
                        className="mt-4 w-full"
                        tabIndex={4}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {t('common.register')}
                    </Button>
                </div>
            </div>
        </div>
    );
};

RegisterNewUser.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.register_new_user.title"
        description="auth.register_new_user.description"
    />
);
export default RegisterNewUser;
