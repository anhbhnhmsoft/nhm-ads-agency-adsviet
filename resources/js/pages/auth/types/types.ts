import { _RoleSystemRequest } from '@/pages/auth/types/constants';

export type LoginRequest = {
    username: string;
    password: string;
    role: _RoleSystemRequest;
    device: 'web';
}
