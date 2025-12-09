import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Phone, MessageCircle, ExternalLink, CheckCircle2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface ContactPageProps {
    contactInfo: {
        telegram: string;
        whatsapp: string;
        channel: string;
    };
    telegramUserInfo: {
        username?: string;
        first_name?: string;
        last_name?: string;
        full_name?: string;
    } | null;
    userTelegramId: string | null;
}

export default function ContactPage({ contactInfo, telegramUserInfo, userTelegramId }: ContactPageProps) {
    const { t } = useTranslation();

    const openTelegram = () => {
        window.open(`https://t.me/${contactInfo.telegram.replace('@', '')}`, '_blank');
    };

    const openWhatsApp = () => {
        window.open(`https://wa.me/${contactInfo.whatsapp.replace(/[^0-9]/g, '')}`, '_blank');
    };

    const openChannel = () => {
        window.open(contactInfo.channel, '_blank');
    };

    return (
        <AppLayout>
            <Head title={t('contact.title', { defaultValue: 'Liên hệ' })} />
            <div className="container mx-auto py-6 space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">{t('contact.title', { defaultValue: 'Liên hệ' })}</h1>
                    <p className="text-muted-foreground mt-2">
                        {t('contact.description', { defaultValue: 'Liên hệ với chúng tôi qua các kênh sau' })}
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Telegram */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageCircle className="h-5 w-5" />
                                {t('contact.telegram', { defaultValue: 'Telegram' })}
                            </CardTitle>
                            <CardDescription>
                                {t('contact.telegram_description', { defaultValue: 'Liên hệ qua Telegram' })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="font-medium">{contactInfo.telegram}</span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={openTelegram}
                                    className="gap-2"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                    {t('contact.open', { defaultValue: 'Mở' })}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* WhatsApp */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Phone className="h-5 w-5" />
                                {t('contact.whatsapp', { defaultValue: 'WhatsApp' })}
                            </CardTitle>
                            <CardDescription>
                                {t('contact.whatsapp_description', { defaultValue: 'Liên hệ qua WhatsApp' })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="font-medium">{contactInfo.whatsapp}</span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={openWhatsApp}
                                    className="gap-2"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                    {t('contact.open', { defaultValue: 'Mở' })}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Channel */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageCircle className="h-5 w-5" />
                                {t('contact.channel', { defaultValue: 'Kênh cập nhật' })}
                            </CardTitle>
                            <CardDescription>
                                {t('contact.channel_description', { defaultValue: 'Theo dõi cập nhật mới' })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground truncate max-w-[200px]">
                                    {contactInfo.channel}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={openChannel}
                                    className="gap-2"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                    {t('contact.open', { defaultValue: 'Mở' })}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Thông tin Telegram của user */}
                {userTelegramId && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {telegramUserInfo ? (
                                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                                ) : (
                                    <XCircle className="h-5 w-5 text-gray-400" />
                                )}
                                {t('contact.your_telegram', { defaultValue: 'Telegram của bạn' })}
                            </CardTitle>
                            <CardDescription>
                                {t('contact.your_telegram_description', { defaultValue: 'Thông tin Telegram đã kết nối' })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {telegramUserInfo ? (
                                <div className="space-y-2">
                                    {telegramUserInfo.username && (
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm text-muted-foreground">
                                                {t('contact.username', { defaultValue: 'Username' })}:
                                            </span>
                                            <Badge variant="outline">@{telegramUserInfo.username}</Badge>
                                        </div>
                                    )}
                                    {telegramUserInfo.full_name && (
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm text-muted-foreground">
                                                {t('contact.name', { defaultValue: 'Tên' })}:
                                            </span>
                                            <span className="font-medium">{telegramUserInfo.full_name}</span>
                                        </div>
                                    )}
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">
                                            {t('contact.telegram_id', { defaultValue: 'Telegram ID' })}:
                                        </span>
                                        <span className="font-mono text-sm">{userTelegramId}</span>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-sm text-muted-foreground">
                                    {t('contact.telegram_info_unavailable', { 
                                        defaultValue: 'Không thể lấy thông tin từ Telegram. Telegram ID: ' 
                                    })}
                                    <span className="font-mono">{userTelegramId}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

