import { _UserRole } from '@/lib/types/constants';
import { IUser } from '@/lib/types/type';
import { useCallback } from 'react';

type AuthLike =
    | IUser
    | {
          user?: IUser | null;
          role?: _UserRole | number | string | null;
          role_id?: _UserRole | number | string | null;
      }
    | null
    | undefined;

const resolveRole = (user: AuthLike): _UserRole | null => {
    if (!user || typeof user !== 'object') {
        return null;
    }

    const resolvedUser =
        'user' in user && user.user && typeof user.user === 'object'
            ? user.user
            : user;
    const roleValue =
        ('role' in resolvedUser ? resolvedUser.role : undefined) ??
        ('role_id' in resolvedUser ? resolvedUser.role_id : undefined);
    const parsedRole = Number(roleValue);

    return Number.isFinite(parsedRole) ? (parsedRole as _UserRole) : null;
};

const useCheckRole = (user: AuthLike) => {
    return useCallback(
        (roles: _UserRole[]) => {
            const resolvedRole = resolveRole(user);
            return resolvedRole !== null && roles.includes(resolvedRole);
        },
        [user],
    );
};
export default useCheckRole;
