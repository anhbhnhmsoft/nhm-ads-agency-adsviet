import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { ProfileUser, TelegramStatus } from '../types/type';

export const useTelegramStatus = (user: ProfileUser): TelegramStatus => {
    const { t } = useTranslation();

    return useMemo(() => {
        if (user.telegram_id) {
            return {
                label: t('user_menu.connected'),
                variant: 'default' as const,
                description: t('profile.telegram_connected', { id: user.telegram_id }),
            };
        }
        return {
            label: t('user_menu.not_connected'),
            variant: 'secondary' as const,
            description: t('profile.telegram_not_connected'),
        };
    }, [user.telegram_id, t]);
};

