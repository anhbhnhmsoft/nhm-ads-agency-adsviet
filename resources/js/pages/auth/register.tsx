import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import TextLink from '@/components/text-link';
import { Head } from '@inertiajs/react';
import { useState, ReactNode } from 'react';
import TelegramButton from './components/TelegramButton';
import AuthLayout from '@/layouts/auth-layout';
import { useTranslation } from 'react-i18next';
import GmailRegisterContainer from './components/GmailRegisterContainer/GmailRegisterContainer';
import { login } from '@/routes';

interface RegisterProps {
  bot_username: string;
}

const RegisterScreen = ({ bot_username }: RegisterProps) => {
  const [method, setMethod] = useState<'telegram' | 'gmail' | null>(null);
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('auth.register_new_user.title')} />
            <CardHeader>
              <CardTitle className="text-center">{t('auth.register_new_user.title')}</CardTitle>
              <p className="text-sm text-gray-500 text-center pb-4">{t('auth.register.choose_social_first')}</p>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex gap-4 justify-center">
                <Button
                  type="button"
                  variant={method === 'telegram' ? 'default' : 'outline'}
                  onClick={() => setMethod('telegram')}
                  className="flex-1 text-lg py-6"
                >
                  {t('common.telegram')}
                </Button>
                <Button
                  type="button"
                  variant={method === 'gmail' ? 'default' : 'outline'}
                  onClick={() => setMethod('gmail')}
                  className="flex-1 text-lg py-6"
                >
                  {t('common.gmail')}
                </Button>
              </div>

              {method === 'telegram' && (
                <div className="mt-4 flex flex-col items-center gap-2">
                  <TelegramButton bot_username={bot_username} />
                  <p className="text-xs text-gray-500 mt-2">{t('auth.register.telegram_help')}</p>
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
            </CardContent>
    </>
  );
};

RegisterScreen.layout = (page: ReactNode) => (
  <AuthLayout
    children={page}
    title="auth.register_new_user.title"
    description="auth.register_new_user.description"
  />
);

export default RegisterScreen;
