import { IUser } from '@/lib/types/type';

export type ProfileUser = Pick<
    IUser,
    'id' | 'name' | 'username' | 'phone' | 'email' | 'telegram_id' | 'whatsapp_id'
> & {
    email_verified_at?: string | null;
};

export type ProfilePageProps = {
    user: ProfileUser;
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

