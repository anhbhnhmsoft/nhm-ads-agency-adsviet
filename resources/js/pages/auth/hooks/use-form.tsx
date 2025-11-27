import { _UserRole } from '@/lib/types/constants';
import { _RoleSystemRequest } from '@/pages/auth/types/constants';
import { LoginRequest, RegisterNewUserRequest } from '@/pages/auth/types/types';
import { auth_login, auth_register_new_user } from '@/routes';
import { useForm } from '@inertiajs/react';
import { MouseEvent } from 'react';

export const useFormLogin = () => {
    const form = useForm<LoginRequest>({
        username: '',
        password: '',
        role: _RoleSystemRequest.USER,
        device: 'web',
    });
    const handleSubmit = (e: MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();
        form.post(auth_login().url);
    };

    return {
        form,
        handleSubmit,
    };
};

export const useFormRegister = () => {
    const form = useForm<RegisterNewUserRequest>({
        role: _UserRole.CUSTOMER,
        name: '',
        username: '',
        password: '',
        type: 'telegram',
        refer_code: '',
        email: '',
    });

    const handleSubmit = (e: MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();
        form.post(auth_register_new_user().url);
    };
    return {
        form,
        handleSubmit,
    };
};
