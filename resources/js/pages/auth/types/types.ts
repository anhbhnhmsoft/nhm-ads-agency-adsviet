import { _RoleSystemRequest } from '@/pages/auth/types/constants';
import { _UserRole } from '@/lib/types/constants';

export type LoginRequest = {
    username: string;
    password: string;
    role: _RoleSystemRequest;
    device: 'web';
}

export type TelegramUser = {
    id: number;
    auth_date: number;
    photo_url: string | null;
    first_name: string;
    last_name: string;
    hash: string;
}

export type RegisterNewUserRequest = {
    role: _UserRole.CUSTOMER | _UserRole.AGENCY;
    name: string;
    username: string;
    password: string;
    refer_code:string;
    type: 'telegram' | 'gmail';
    email?: string;
}
