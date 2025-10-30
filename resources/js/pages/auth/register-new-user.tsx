import AuthLayout from '@/layouts/auth-layout';
import CustomerRoleCard from '@/pages/auth/components/CustomerRoleCard';
import { useFormRegister } from '@/pages/auth/hooks/use-form';
import { TelegramUser, WhatsAppUser } from '@/pages/auth/types/types';
import { Head } from '@inertiajs/react';
import { ReactNode, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';

type Props = {
    social_data: {
        type: 'telegram' | 'whatsapp';
        data: TelegramUser | WhatsAppUser;
    };
};

const RegisterNewUser = ({ social_data }: Props) => {
    const { t } = useTranslation();

    const { form, handleSubmit } = useFormRegister();

    const { data, setData, processing, errors } = form;

    useEffect(() => {
        // telegram
        if (social_data.type === 'telegram') {
            const user = social_data.data as TelegramUser;
            setData('type', 'telegram');
            setData('name', user.first_name + ' ' + user.last_name);
        }
    }, [social_data]);

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
                            onChange={(e) =>
                                setData('name', e.target.value)
                            }
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

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">
                                <span className="text-red-500">*</span>
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
