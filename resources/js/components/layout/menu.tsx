import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';
import { IMenu } from '@/lib/types/type';
import { resolveUrl } from '@/lib/utils';
import {
    dashboard,
    service_packages_create_view,
    service_packages_index,
    user_list,
    user_list_employee,
    config_index,
    wallet_index,
    service_purchase_index,
    service_orders_index,
} from '@/routes';
import { InertiaLinkProps, usePage } from '@inertiajs/react';
import {
    BookUser,
    Boxes,
    LayoutDashboard,
    Settings,
    Users,
    Wallet,
    Receipt,
    ShoppingCart,
    ClipboardList,
} from 'lucide-react';
import { useCallback, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
const useMenu = () => {
    const { url, props } = usePage();
    const checkRole = useCheckRole(props.auth);
    const { t } = useTranslation();

    const isActive = useCallback(
        (href: NonNullable<InertiaLinkProps['href']>) => {
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
                title: t('menu.my_wallet'),
                url: wallet_index().url,
                icon: <Wallet />,
                is_menu: true,
                active: isActive(wallet_index()),
                can_show: checkRole([_UserRole.ADMIN, _UserRole.CUSTOMER, _UserRole.AGENCY]),
            },
            {
                title: t('menu.service_purchase'),
                url: service_purchase_index().url,
                icon: <ShoppingCart />,
                is_menu: true,
                active: isActive(service_purchase_index()),
                can_show: checkRole([_UserRole.CUSTOMER, _UserRole.AGENCY]),
            },
            {
                title: checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE])
                    ? t('menu.service_orders_admin')
                    : t('menu.service_orders'),
                url: service_orders_index().url,
                icon: <ClipboardList />,
                is_menu: true,
                active: isActive(service_orders_index()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                    _UserRole.EMPLOYEE,
                    _UserRole.CUSTOMER,
                    _UserRole.AGENCY,
                ]),
            },
            {
                title: t('menu.transactions'),
                url: '/transactions',
                icon: <Receipt />,
                is_menu: true,
                active: isActive('/transactions'),
                can_show: checkRole([_UserRole.ADMIN, _UserRole.CUSTOMER, _UserRole.AGENCY, _UserRole.EMPLOYEE, _UserRole.MANAGER]),
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
                can_show: checkRole([_UserRole.ADMIN, _UserRole.MANAGER]),
            },
            {
                title: t('menu.user_list_customer'),
                url: user_list().url,
                icon: <BookUser />,
                is_menu: true,
                active: isActive(user_list()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                    _UserRole.EMPLOYEE,
                    _UserRole.AGENCY,
                ]),
            },
            {
                title: t('menu.config'),
                is_menu: false,
                can_show: checkRole([_UserRole.ADMIN]),
            },
            {
                title: t('menu.platform_settings'),
                url: '/platform-settings',
                icon: <Settings />,
                is_menu: true,
                active: isActive('/platform-settings'),
                can_show: checkRole([_UserRole.ADMIN]),
            },
            {
                title: t('menu.service_packages'),
                url: service_packages_index().url,
                icon: <Boxes />,
                is_menu: true,
                active:
                    isActive(service_packages_index()) ||
                    isActive(service_packages_create_view()),
                can_show: checkRole([_UserRole.ADMIN]),
            },
            {
                title: t('menu.crypto_wallet_config'),
                url: config_index().url,
                icon: <Wallet />,
                is_menu: true,
                active: isActive(config_index()),
                can_show: checkRole([_UserRole.ADMIN]),
            },
            

        ];
    }, [checkRole, t, isActive]);

    return menu;
};

export default useMenu;
