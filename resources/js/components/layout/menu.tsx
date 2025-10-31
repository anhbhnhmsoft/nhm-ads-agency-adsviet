import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';
import { IMenu } from '@/lib/types/type';
import { resolveUrl } from '@/lib/utils';
import { dashboard, user_list_employee } from '@/routes';
import { InertiaLinkProps, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

const useMenu = () => {
    const { url, props } = usePage();
    const checkRole = useCheckRole(props.auth);
    const { t } = useTranslation();

    const isActive = useCallback(
        (href:  NonNullable<InertiaLinkProps["href"]>) => {
            return url.startsWith(resolveUrl(href));
        },
        [url],
    );
    const menu: IMenu[] = useMemo(() => {
        return [
            {
                title: t('menu.dashboard'),
                url: dashboard().url,
                icon: <LayoutDashboard />,
                is_menu: true,
                active: isActive(dashboard()),
                can_show: true,
            },
            {
                title: t('menu.user'),
                is_menu: false,
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.AGENCY,
                    _UserRole.EMPLOYEE,
                    _UserRole.MANAGER,
                ]),
            },
            {
                title: t('menu.user_list_employee'),
                url: user_list_employee().url,
                icon: <Users />,
                is_menu: true,
                active: isActive(user_list_employee()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                ]),
            },
        ];
    }, [checkRole, t, isActive]);

    return menu;
};

export default useMenu;
