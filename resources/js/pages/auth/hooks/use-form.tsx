import { useForm } from '@inertiajs/react';
import { LoginRequest } from '@/pages/auth/types/types';
import { _RoleSystemRequest } from '@/pages/auth/types/constants';
import { MouseEvent } from 'react';
import { login_username } from '@/routes';

export const useFormLogin = () => {
    const form = useForm<LoginRequest>({
        username: '',
        password: '',
        role: _RoleSystemRequest.USER,
        device: 'web'
    });
    const handleSubmit = (e: MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();
        form.post(login_username().url);
    };

    return {
        form,
        handleSubmit,
    }
};
