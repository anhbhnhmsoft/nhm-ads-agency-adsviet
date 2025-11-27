import { ReactNode, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { Head } from '@inertiajs/react';
import { profile } from '@/routes';
import BasicInfoCard from './components/BasicInfoCard';
import ConnectionsCard from './components/ConnectionsCard';
import ChangePasswordCard from './components/ChangePasswordCard';
import { useEmailStatus } from './hooks/use-email-status';
import { useTelegramStatus } from './hooks/use-telegram-status';
import { useEmailOtpSent } from './hooks/use-email-otp-sent';
import type { ProfilePageProps } from './types/type';

const ProfilePage = ({ user }: ProfilePageProps) => {
    const { t } = useTranslation();
    const emailStatus = useEmailStatus(user);
    const telegramStatus = useTelegramStatus(user);
    const { emailOtpSent, setEmailOtpSent, resetOtpSent } = useEmailOtpSent();

    // Reset khi email đã được verify
    useEffect(() => {
        if (user.email_verified_at) {
            resetOtpSent();
        }
    }, [user.email_verified_at, resetOtpSent]);

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

