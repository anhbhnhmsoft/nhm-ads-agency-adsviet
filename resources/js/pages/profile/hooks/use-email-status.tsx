import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { ProfileUser, EmailStatus } from '../types/type';

export const useEmailStatus = (user: ProfileUser): EmailStatus => {
    const { t } = useTranslation();

    return useMemo(() => {
        if (!user.email) {
            return {
                label: t('user_menu.not_connected'),
                variant: 'secondary' as const,
                description: t('profile.email_not_connected'),
            };
        }
        if (!user.email_verified_at) {
            return {
                label: t('user_menu.pending'),
                variant: 'outline' as const,
                description: t('profile.email_pending', { email: user.email }),
            };
        }
        return {
            label: t('user_menu.connected'),
            variant: 'default' as const,
            description: user.email,
        };
    }, [user.email, user.email_verified_at, t]);
};

