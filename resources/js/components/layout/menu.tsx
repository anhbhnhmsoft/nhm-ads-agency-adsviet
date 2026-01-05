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
    service_management_index,
    spend_report_index,
    ticket_index,
    ticket_transfer,
    ticket_refund,
    ticket_appeal,
    ticket_share,
    ticket_withdraw_app,
    ticket_deposit_app,
    business_managers_index,
    contact_index,
    ticket_create_account,
} from '@/routes';
import { InertiaLinkProps, usePage } from '@inertiajs/react';
import {
    BookUser,
    Boxes,
    LayoutDashboard,
    Settings,
    Users,
    Wallet,
    ShoppingCart,
    KanbanSquare,
    MessageSquare,
    DollarSign,
    Phone,
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
            // Trang chủ
            {
                title: t('menu.dashboard'),
                url: dashboard().url,
                icon: <LayoutDashboard />,
                is_menu: true,
                active: isActive(dashboard()),
                can_show: true,
            },
            // Quản lý tài khoản
            {
                title: t('menu.service_management'),
                url: service_management_index().url,
                icon: <KanbanSquare />,
                is_menu: true,
                active: isActive(service_management_index()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                    _UserRole.EMPLOYEE,
                    _UserRole.CUSTOMER,
                    _UserRole.AGENCY,
                ]),
            },
            // Quản lý tài chính (dropdown)
            {
                title: t('menu.financial_management'),
                icon: <DollarSign />,
                is_menu: true,
                active: isActive('/transactions') || 
                        isActive(spend_report_index()) || 
                        isActive(wallet_index()) || 
                        isActive(service_orders_index()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                    _UserRole.EMPLOYEE,
                    _UserRole.CUSTOMER,
                    _UserRole.AGENCY,
                ]),
                items: [
                    {
                        title: t('menu.transactions'),
                        url: '/transactions',
                        active: isActive('/transactions'),
                        can_show: checkRole([_UserRole.ADMIN, _UserRole.CUSTOMER, _UserRole.AGENCY, _UserRole.EMPLOYEE, _UserRole.MANAGER]),
                    },
                    {
                        title: t('menu.spend_report'),
                        url: spend_report_index().url,
                        active: isActive(spend_report_index()),
                        can_show: checkRole([_UserRole.CUSTOMER, _UserRole.AGENCY]),
                    },
                    {
                        title: t('menu.my_wallet'),
                        url: wallet_index().url,
                        active: isActive(wallet_index()),
                        can_show: checkRole([_UserRole.CUSTOMER, _UserRole.AGENCY]),
                    },
                    {
                        title: checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE])
                            ? t('menu.service_orders_admin')
                            : t('menu.service_orders'),
                        url: service_orders_index().url,
                        active: isActive(service_orders_index()),
                        can_show: checkRole([
                            _UserRole.ADMIN,
                            _UserRole.MANAGER,
                            _UserRole.EMPLOYEE,
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                ],
            },
            // Mua gói dịch vụ
            {
                title: t('menu.service_purchase'),
                url: service_purchase_index().url,
                icon: <ShoppingCart />,
                is_menu: true,
                active: isActive(service_purchase_index()),
                can_show: checkRole([_UserRole.CUSTOMER, _UserRole.AGENCY]),
            },
            // Hỗ trợ
            {
                title: checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE])
                    ? t('menu.support_customer')
                    : t('menu.support'),
                icon: <MessageSquare />,
                is_menu: true,
                active: isActive(ticket_index()) || isActive(ticket_transfer()) || isActive(ticket_refund()) || isActive(ticket_appeal()) || isActive(ticket_share()),
                can_show: checkRole([
                    _UserRole.ADMIN,
                    _UserRole.MANAGER,
                    _UserRole.EMPLOYEE,
                    _UserRole.CUSTOMER,
                    _UserRole.AGENCY,
                ]),
                items: [
                    {
                        title: t('ticket.list', { defaultValue: 'Quản lý hỗ trợ' }),
                        url: ticket_index().url,
                        active: isActive(ticket_index()),
                        can_show: checkRole([
                            _UserRole.ADMIN,
                            _UserRole.MANAGER,
                            _UserRole.EMPLOYEE,
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.create_account.title', { defaultValue: 'Tạo tài khoản' }),
                        url: ticket_create_account().url,
                        active: isActive(ticket_create_account()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.transfer.title', { defaultValue: 'Chuyển tiền' }),
                        url: ticket_transfer().url,
                        active: isActive(ticket_transfer()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.refund.title', { defaultValue: 'Thanh lý tài khoản' }),
                        url: ticket_refund().url,
                        active: isActive(ticket_refund()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.appeal.title', { defaultValue: 'Kháng tài khoản' }),
                        url: ticket_appeal().url,
                        active: isActive(ticket_appeal()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.share.title', { defaultValue: 'Share BM/BC/MCC' }),
                        url: ticket_share().url,
                        active: isActive(ticket_share()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.withdraw_app.title', { defaultValue: 'Rút ví app về ví khách' }),
                        url: ticket_withdraw_app().url,
                        active: isActive(ticket_withdraw_app()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                    {
                        title: t('ticket.deposit_app.title', { defaultValue: 'Nạp tiền vào tài khoản' }),
                        url: ticket_deposit_app().url,
                        active: isActive(ticket_deposit_app()),
                        can_show: checkRole([
                            _UserRole.CUSTOMER,
                            _UserRole.AGENCY,
                        ]),
                    },
                ],
            },
            {
                title: t('menu.contact'),
                url: contact_index().url,
                icon: <Phone />,
                is_menu: true,
                active: isActive(contact_index()),
                can_show: checkRole([
                    _UserRole.CUSTOMER,
                    _UserRole.AGENCY,
                ]),
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
                title: t('menu.business_managers', { defaultValue: 'Quản lý BM/MCC' }),
                url: business_managers_index().url,
                icon: <Settings />,
                is_menu: true,
                active: isActive(business_managers_index()),
                can_show: checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE, _UserRole.CUSTOMER, _UserRole.AGENCY]),
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
