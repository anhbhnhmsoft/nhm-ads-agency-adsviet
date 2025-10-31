import { FC, ReactNode } from 'react';
import { _UserRole } from '@/lib/types/constants';
import useCheckRole from '@/hooks/use-check-role';
import { usePage } from '@inertiajs/react';

type Props = {
    roles: _UserRole[],
    render: () => ReactNode,
    fallback?: () => ReactNode,
}
const ControlPermission: FC<Props> = ({ roles, render, fallback }) => {
    const {auth} = usePage().props;
    const checkRole = useCheckRole(auth);

    if (checkRole(roles)) {
        return <>{render()}</>;
    }
    return fallback ? <>{fallback()}</> : null;

}
export default ControlPermission;




