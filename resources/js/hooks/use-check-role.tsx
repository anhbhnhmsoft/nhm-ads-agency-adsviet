import { _UserRole } from '@/lib/types/constants';
import { IUser } from '@/lib/types/type';
import { useCallback } from 'react';

const useCheckRole = (user: IUser | null) => {
    return useCallback(
        (roles: _UserRole[]) => {
            if (user) {
                return roles.includes(user.role);
            }
            return false;
        },
        [user],
    );
};
export default useCheckRole;
