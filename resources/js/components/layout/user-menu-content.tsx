import {
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/layout/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { Link, router } from '@inertiajs/react';
import { LogOut, Mail, Send, UserRound } from 'lucide-react';
import { IUser } from '@/lib/types/type';
import { useTranslation } from 'react-i18next';
import { logout, profile } from '@/routes';
import { Badge } from '@/components/ui/badge';

interface UserMenuContentProps {
    user: IUser | null;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();
    const { t } = useTranslation();

    const handleLogout = () => {
        cleanup();
        router.post(logout());
    };

    const emailStatus = (() => {
        if (!user?.email) {
            return {
                label: t('user_menu.not_connected'),
                variant: 'secondary' as const,
                description: t('user_menu.connect_email_hint'),
            };
        }
        if (!user?.email_verified_at) {
            return {
                label: t('user_menu.pending'),
                variant: 'outline' as const,
                description: t('user_menu.connect_email_pending_hint', {
                    email: user.email,
                }),
            };
        }
        return {
            label: t('user_menu.connected'),
            variant: 'default' as const,
            description: user.email,
        };
    })();

    const telegramStatus = user?.telegram_id
        ? {
              label: t('user_menu.connected'),
              variant: 'default' as const,
              description: t('user_menu.connect_telegram_connected_hint', {
                  id: user.telegram_id,
              }),
          }
        : {
              label: t('user_menu.not_connected'),
              variant: 'secondary' as const,
              description: t('user_menu.connect_telegram_hint'),
          };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    href={profile().url}
                    className="flex items-center gap-2"
                    onClick={() => cleanup()}
                >
                    <UserRound className="size-4 text-muted-foreground" />
                    <span>{t('user_menu.profile')}</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                className="flex flex-col items-start gap-2 py-3"
                onSelect={(event) => event.preventDefault()}
            >
                <div className="flex w-full items-center gap-3">
                    <Mail className="size-4 text-muted-foreground" />
                    <div className="flex flex-1 flex-col gap-1">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-sm font-medium">
                                {t('user_menu.connect_email')}
                            </span>
                            <Badge variant={emailStatus.variant}>
                                {emailStatus.label}
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {emailStatus.description}
                        </p>
                    </div>
                </div>
            </DropdownMenuItem>
            <DropdownMenuItem
                className="flex flex-col items-start gap-2 py-3"
                onSelect={(event) => event.preventDefault()}
            >
                <div className="flex w-full items-center gap-3">
                    <Send className="size-4 text-muted-foreground" />
                    <div className="flex flex-1 flex-col gap-1">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-sm font-medium">
                                {t('user_menu.connect_telegram')}
                            </span>
                            <Badge variant={telegramStatus.variant}>
                                {telegramStatus.label}
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {telegramStatus.description}
                        </p>
                    </div>
                </div>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                onSelect={(event) => {
                    event.preventDefault();
                    handleLogout();
                }}
                data-test="logout-button"
            >
                <LogOut className="mr-2" />
                {t('common.logout')}
            </DropdownMenuItem>
        </>
    );
}
