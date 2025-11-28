import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Mail, Send } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { ProfileUser, EmailStatus, TelegramStatus } from '../types/type';

type Props = {
    user: ProfileUser;
    emailStatus: EmailStatus;
    telegramStatus: TelegramStatus;
    onTelegramConnect?: () => void;
    canConnectTelegram?: boolean;
    telegramConnecting?: boolean;
};

type ConnectionItemProps = {
    icon: typeof Mail;
    title: string;
    status: string;
    variant: 'default' | 'secondary' | 'outline';
    description: string;
    action?: React.ReactNode;
};

const ConnectionItem = ({ icon: Icon, title, status, variant, description, action }: ConnectionItemProps) => {
    return (
        <div className="rounded-lg border p-4">
            <div className="flex items-center gap-3">
                <div className="rounded-full bg-muted p-2">
                    <Icon className="size-4 text-muted-foreground" />
                </div>
                <div className="flex flex-1 flex-col gap-1">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-sm font-medium">{title}</span>
                        <div className="flex items-center gap-2">
                            {action}
                            <Badge variant={variant}>{status}</Badge>
                        </div>
                    </div>
                    <p className="text-xs text-muted-foreground">{description}</p>
                </div>
            </div>
        </div>
    );
};

const ConnectionsCard = ({
    user,
    emailStatus,
    telegramStatus,
    onTelegramConnect,
    canConnectTelegram = false,
    telegramConnecting = false,
}: Props) => {
    const { t } = useTranslation();
    const isTelegramConnected = Boolean(user.telegram_id);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('profile.connections')}</CardTitle>
                <CardDescription>{t('profile.connections_description')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <ConnectionItem
                    icon={Mail}
                    title={t('profile.email_connection')}
                    status={emailStatus.label}
                    variant={emailStatus.variant}
                    description={emailStatus.description}
                />
                <ConnectionItem
                    icon={Send}
                    title={t('profile.telegram_connection')}
                    status={telegramStatus.label}
                    variant={telegramStatus.variant}
                    description={telegramStatus.description}
                    action={
                        !isTelegramConnected && onTelegramConnect ? (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={onTelegramConnect}
                                disabled={!canConnectTelegram || telegramConnecting}
                            >
                                {telegramConnecting ? t('profile.telegram_connecting') : t('profile.telegram_connect_button')}
                            </Button>
                        ) : undefined
                    }
                />
                {!isTelegramConnected && !canConnectTelegram && (
                    <p className="text-xs text-muted-foreground">{t('profile.telegram_connect_unavailable')}</p>
                )}
            </CardContent>
        </Card>
    );
};

export default ConnectionsCard;

