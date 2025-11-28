import { ReactNode, useEffect, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { Head, router } from '@inertiajs/react';
import { profile, profile_connect_telegram } from '@/routes';
import BasicInfoCard from './components/BasicInfoCard';
import ConnectionsCard from './components/ConnectionsCard';
import ChangePasswordCard from './components/ChangePasswordCard';
import { useEmailStatus } from './hooks/use-email-status';
import { useTelegramStatus } from './hooks/use-telegram-status';
import { useEmailOtpSent } from './hooks/use-email-otp-sent';
import type { ProfilePageProps, TelegramAuthData } from './types/type';

const ProfilePage = ({ user, telegram }: ProfilePageProps) => {
    const { t } = useTranslation();
    const emailStatus = useEmailStatus(user);
    const telegramStatus = useTelegramStatus(user);
    const { emailOtpSent, setEmailOtpSent, resetOtpSent } = useEmailOtpSent();
    const [telegramConnecting, setTelegramConnecting] = useState(false);

    const telegramOAuthUrl = useMemo(() => {
        if (!telegram?.bot_id || !telegram?.callback_url) {
            return null;
        }
        return getTelegramUrlOAuth(telegram.bot_id, telegram.callback_url);
    }, [telegram]);

    const handleTelegramConnect = () => {
        if (!telegramOAuthUrl) {
            return;
        }
        window.location.href = telegramOAuthUrl;
    };

    // Reset khi email đã được verify
    useEffect(() => {
        if (user.email_verified_at) {
            resetOtpSent();
        }
    }, [user.email_verified_at, resetOtpSent]);

    useEffect(() => {
        const marker = '#tgAuthResult=';
        const href = window.location.href;
        
        if (!href.includes(marker)) {
            return;
        }
        
        const fragment = href.split(marker)[1];
        if (!fragment) {
            console.warn('Telegram OAuth: Fragment is empty');
            return;
        }
        
        try {
            // Decode base64url
            let base64 = fragment.replace(/-/g, '+').replace(/_/g, '/');
            // Add padding if needed
            while (base64.length % 4) {
                base64 += '=';
            }
            
            const decoded = atob(base64);
            const data: TelegramAuthData = JSON.parse(decoded);
            
            // Validate required fields
            if (!data.id || !data.auth_date || !data.hash) {
                console.error('Telegram OAuth: Missing required fields', data);
                window.location.hash = '';
                return;
            }
            
            // Convert id to string as required by backend
            const payload = {
                ...data,
                id: String(data.id),
            };
            
            console.log('Telegram OAuth: Parsed data', payload);
            setTelegramConnecting(true);
            
            router.post(profile_connect_telegram().url, payload, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Telegram OAuth: Connection successful');
                    setTelegramConnecting(false);
                    window.location.hash = '';
                },
                onError: (errors) => {
                    console.error('Telegram OAuth: Connection failed', errors);
                    setTelegramConnecting(false);
                    window.location.hash = '';
                },
                onFinish: () => {
                    setTelegramConnecting(false);
                    window.location.hash = '';
                },
            });
        } catch (error) {
            console.error('Telegram OAuth parse error', error);
            setTelegramConnecting(false);
            window.location.hash = '';
        }
    }, []);

    return (
        <>
            <Head title={t('profile.title')} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{t('profile.title')}</h1>
                    <p className="text-sm text-muted-foreground">{t('profile.description')}</p>
                </div>
                <div className="grid gap-6 lg:grid-cols-2">
                    <BasicInfoCard
                        user={user}
                        emailOtpSent={emailOtpSent}
                        onOtpSentChange={setEmailOtpSent}
                    />
                    <ConnectionsCard
                        user={user}
                        emailStatus={emailStatus}
                        telegramStatus={telegramStatus}
                        canConnectTelegram={Boolean(telegramOAuthUrl)}
                        telegramConnecting={telegramConnecting}
                        onTelegramConnect={handleTelegramConnect}
                    />
                </div>
                <ChangePasswordCard />
            </div>
        </>
    );
};

ProfilePage.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'menu.profile', href: profile().url }]} children={page} />
);

export default ProfilePage;

function getTelegramUrlOAuth(botId: string, callbackUrl: string) {
    return `https://oauth.telegram.org/auth?bot_id=${botId}&origin=${encodeURIComponent(
        callbackUrl
    )}&embed=1&request_access=write&return_to=${encodeURIComponent(callbackUrl)}`;
}

