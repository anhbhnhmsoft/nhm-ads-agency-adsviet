import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import type { TicketConversation, TicketReplySide } from '../types/type';
import { _TicketReplySide } from '../types/constants';
import { MessageSquare, User } from 'lucide-react';
// Helper function to format relative time
const formatRelativeTime = (date: string): string => {
    const now = new Date();
    const messageDate = new Date(date);
    const diffInSeconds = Math.floor((now.getTime() - messageDate.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return 'vừa xong';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} phút trước`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} giờ trước`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ngày trước`;
    } else {
        return messageDate.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }
};

interface TicketMessagesProps {
    conversations: TicketConversation[];
}

export function TicketMessages({ conversations }: TicketMessagesProps) {
    const { t } = useTranslation();

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
                                            {formatRelativeTime(conversation.created_at)}
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

