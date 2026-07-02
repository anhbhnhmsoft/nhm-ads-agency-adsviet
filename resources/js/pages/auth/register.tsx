import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { Head } from '@inertiajs/react';
import { ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';
import GmailRegisterContainer from './components/GmailRegisterContainer/GmailRegisterContainer';
import TelegramButton from './components/TelegramButton';

interface RegisterProps {
    bot_username: string;
}

const RegisterScreen = ({ bot_username }: RegisterProps) => {
    const [method, setMethod] = useState<'telegram' | 'gmail' | null>(null);
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Head title={t('auth.register_new_user.title')} />
            <div className="flex justify-center gap-4">
                <Button
                    type="button"
                    variant={method === 'telegram' ? 'default' : 'outline'}
                    onClick={() => setMethod('telegram')}
                    className="flex-1 py-6 text-lg"
                >
                    {t('common.telegram')}
                </Button>
                <Button
                    type="button"
                    variant={method === 'gmail' ? 'default' : 'outline'}
                    onClick={() => setMethod('gmail')}
                    className="flex-1 py-6 text-lg"
                >
                    {t('common.gmail')}
                </Button>
            </div>

            {method === 'telegram' && (
                <div className="mt-4 flex flex-col items-center gap-2">
                    <TelegramButton bot_username={bot_username} />
                    <p className="mt-2 text-xs text-gray-500">
                        {t('auth.register.telegram_help')}
                    </p>
                </div>
            )}
            {method === 'gmail' && (
                <div className="mt-4">
                    <GmailRegisterContainer />
                </div>
            )}
            <div className="text-center text-sm text-muted-foreground">
                {t('auth.register.back_to_login_prompt')}{' '}
                <TextLink href={login().url}>
                    {t('auth.register.back_to_login_link')}
                </TextLink>
            </div>
        </div>
    );
};

RegisterScreen.layout = (page: ReactNode) => (
    <AuthLayout
        children={page}
        title="auth.register_new_user.title"
        description="auth.register.choose_social_first"
    />
);

export default RegisterScreen;
