import { IUser } from '@/lib/types/type';

export type ProfileUser = Pick<
    IUser,
    'id' | 'name' | 'username' | 'phone' | 'email' | 'telegram_id' | 'whatsapp_id' | 'referral_code'
> & {
    email_verified_at?: string | null;
};

export type ProfilePageProps = {
    user: ProfileUser;
    telegram: {
        bot_id?: string | null;
        callback_url: string;
    };
};

export type TelegramAuthData = {
    id: string;
    first_name?: string;
    last_name?: string;
    username?: string;
    photo_url?: string;
    auth_date: number;
    hash: string;
};

export type EmailStatus = {
    label: string;
    variant: 'default' | 'secondary' | 'outline';
    description: string;
};

export type TelegramStatus = {
    label: string;
    variant: 'default' | 'secondary' | 'outline';
    description: string;
};

