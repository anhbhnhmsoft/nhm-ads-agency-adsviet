import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import type { TicketConversation, TicketReplySide } from '../types/type';
import { _TicketReplySide } from '../types/constants';
import { MessageSquare, User } from 'lucide-react';

// Helper function to format relative time, respecting current locale
const formatRelativeTime = (date: string, locale: string): string => {
    const now = new Date();
    const messageDate = new Date(date);
    const diffInSeconds = Math.round((messageDate.getTime() - now.getTime()) / 1000);
    const formatter = new Intl.RelativeTimeFormat(locale || 'en', { numeric: 'auto' });

    if (Math.abs(diffInSeconds) < 60) {
        return formatter.format(diffInSeconds, 'second');
    }

    const diffInMinutes = Math.round(diffInSeconds / 60);
    if (Math.abs(diffInMinutes) < 60) {
        return formatter.format(diffInMinutes, 'minute');
    }

    const diffInHours = Math.round(diffInMinutes / 60);
    if (Math.abs(diffInHours) < 24) {
        return formatter.format(diffInHours, 'hour');
    }

    const diffInDays = Math.round(diffInHours / 24);
    if (Math.abs(diffInDays) < 7) {
        return formatter.format(diffInDays, 'day');
    }

    return messageDate.toLocaleDateString(locale || 'en', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

interface TicketMessagesProps {
    conversations: TicketConversation[];
}

export function TicketMessages({ conversations }: TicketMessagesProps) {
    const { t, i18n } = useTranslation();

    if (!conversations || conversations.length === 0) {
        return (
            <Card>
                <CardContent className="flex flex-col items-center justify-center py-12">
                    <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                    <p className="text-muted-foreground">
                        {t('ticket.no_messages', { defaultValue: 'Chưa có tin nhắn nào' })}
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-4">
            {conversations.map((conversation) => {
                const isCustomer = conversation.reply_side === _TicketReplySide.CUSTOMER;
                return (
                    <Card
                        key={conversation.id}
                        className={isCustomer ? 'bg-muted/30' : 'bg-primary/5'}
                    >
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                                    <User className="h-5 w-5" />
                                </div>
                                <div className="flex-1">
                                    <div className="mb-2 flex items-center gap-2">
                                        <span className="font-semibold">
                                            {conversation.user?.name || t('ticket.reply_side.customer')}
                                        </span>
                                        <Badge variant={isCustomer ? 'outline' : 'default'}>
                                            {isCustomer
                                                ? t('ticket.reply_side.customer')
                                                : t('ticket.reply_side.staff')}
                                        </Badge>
                                        <span className="text-sm text-muted-foreground">
                                            {formatRelativeTime(conversation.created_at, i18n.language)}
                                        </span>
                                    </div>
                                    <p className="whitespace-pre-wrap text-sm">{conversation.message}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

