import { _UserRole } from '@/lib/types/constants';
import { useCallback } from 'react';
import { IUser } from '@/lib/types/type';

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
