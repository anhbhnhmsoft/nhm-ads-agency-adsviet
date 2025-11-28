import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { IBreadcrumbItem } from '@/lib/types/type';
import { ticket_index, ticket_update_status } from '@/routes';
import { Head, Link, useForm, usePage, usePoll } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { TicketDetailPageProps, TicketStatus, TicketPriority } from './types/type';
import { _TicketStatus, _TicketPriority } from './types/constants';
import { TicketMessages } from './components/TicketMessages';
import { SendMessageForm } from './components/SendMessageForm';
import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';
import { useMemo, useEffect } from 'react';

const breadcrumbs: IBreadcrumbItem[] = [
    {
        title: 'Hỗ trợ',
        href: ticket_index().url,
    },
];

export default function TicketShow({ ticket }: TicketDetailPageProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const authUser = useMemo(() => {
        const authProp = props.auth as { user?: any } | any | null | undefined;
        if (authProp && typeof authProp === 'object' && 'user' in authProp) {
            return authProp.user ?? null;
        }
        return (authProp as any | null) ?? null;
    }, [props.auth]);
    const checkRole = useCheckRole(authUser);

    const statusForm = useForm({
        status: ticket?.status ?? _TicketStatus.PENDING,
    });

    if (!ticket) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={t('ticket.not_found')} />
                <div className="flex h-full flex-1 flex-col items-center justify-center gap-4">
                    <p className="text-muted-foreground">{t('ticket.not_found')}</p>
                    <Button asChild>
                        <Link href={ticket_index().url}>{t('common.back', { defaultValue: 'Quay lại' })}</Link>
                    </Button>
                </div>
            </AppLayout>
        );
    }

    const isStaff = checkRole([_UserRole.ADMIN, _UserRole.MANAGER, _UserRole.EMPLOYEE]);
    const canUpdateStatus = isStaff;

    const getStatusBadge = (status: TicketStatus) => {
        const statusMap: Record<TicketStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            [_TicketStatus.PENDING]: { label: t('ticket.status.pending'), variant: 'secondary' },
            [_TicketStatus.OPEN]: { label: t('ticket.status.open'), variant: 'default' },
            [_TicketStatus.IN_PROGRESS]: { label: t('ticket.status.in_progress'), variant: 'default' },
            [_TicketStatus.RESOLVED]: { label: t('ticket.status.resolved'), variant: 'outline' },
            [_TicketStatus.CLOSED]: { label: t('ticket.status.closed'), variant: 'secondary' },
        };
        const statusInfo = statusMap[status] || statusMap[_TicketStatus.PENDING];
        return <Badge variant={statusInfo.variant}>{statusInfo.label}</Badge>;
    };

    const getPriorityBadge = (priority: TicketPriority) => {
        const priorityMap: Record<TicketPriority, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
            [_TicketPriority.LOW]: { label: t('ticket.priority.low'), variant: 'outline' },
            [_TicketPriority.MEDIUM]: { label: t('ticket.priority.medium'), variant: 'default' },
            [_TicketPriority.HIGH]: { label: t('ticket.priority.high'), variant: 'default' },
            [_TicketPriority.URGENT]: { label: t('ticket.priority.urgent'), variant: 'destructive' },
        };
        const priorityInfo = priorityMap[priority] || priorityMap[_TicketPriority.MEDIUM];
        return <Badge variant={priorityInfo.variant}>{priorityInfo.label}</Badge>;
    };

    const handleStatusChange = (newStatus: string) => {
        statusForm.setData('status', parseInt(newStatus) as TicketStatus);
        statusForm.put(ticket_update_status({ id: ticket.id }).url, {
            preserveScroll: true,
        });
    };

    // Polling để tự động reload messages khi có tin nhắn mới
    const { start: startPolling, stop: stopPolling } = usePoll(
        5000,
        {
            only: ['ticket'],
        },
        {
            autoStart: false,
        }
    );

    useEffect(() => {
        if (!ticket) {
            stopPolling();
            return;
        }

        if (ticket.status !== _TicketStatus.CLOSED) {
            startPolling();
        } else {
            stopPolling();
        }

        return () => {
            stopPolling();
        };
    }, [ticket?.id, ticket?.status, startPolling, stopPolling]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={ticket.subject} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={ticket_index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold">{ticket.subject}</h1>
                        <div className="mt-2 flex items-center gap-2">
                            {getStatusBadge(ticket.status)}
                            {getPriorityBadge(ticket.priority)}
                        </div>
                    </div>
                    {canUpdateStatus && (
                        <div className="flex items-center gap-2">
                            <Label>{t('ticket.status_label', { defaultValue: 'Trạng thái' })}</Label>
                            <Select
                                value={ticket.status.toString()}
                                onValueChange={handleStatusChange}
                                disabled={statusForm.processing}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={_TicketStatus.PENDING.toString()}>{t('ticket.status.pending')}</SelectItem>
                                    <SelectItem value={_TicketStatus.OPEN.toString()}>{t('ticket.status.open')}</SelectItem>
                                    <SelectItem value={_TicketStatus.IN_PROGRESS.toString()}>{t('ticket.status.in_progress')}</SelectItem>
                                    <SelectItem value={_TicketStatus.RESOLVED.toString()}>{t('ticket.status.resolved')}</SelectItem>
                                    <SelectItem value={_TicketStatus.CLOSED.toString()}>{t('ticket.status.closed')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {/* Ticket Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('ticket.detail', { defaultValue: 'Chi tiết yêu cầu hỗ trợ' })}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label className="text-muted-foreground">
                                {t('ticket.description', { defaultValue: 'Mô tả' })}
                            </Label>
                            <p className="mt-1 whitespace-pre-wrap">{ticket.description}</p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label className="text-muted-foreground">
                                    {t('ticket.created_by', { defaultValue: 'Người tạo' })}
                                </Label>
                                <p className="mt-1">{ticket.user?.name || ticket.user_id}</p>
                            </div>
                            {ticket.assignedUser && (
                                <div>
                                    <Label className="text-muted-foreground">
                                        {t('ticket.assigned_to', { defaultValue: 'Người xử lý' })}
                                    </Label>
                                    <p className="mt-1">{ticket.assignedUser.name}</p>
                                </div>
                            )}
                            <div>
                                <Label className="text-muted-foreground">
                                    {t('ticket.created_at', { defaultValue: 'Ngày tạo' })}
                                </Label>
                                <p className="mt-1">
                                    {new Date(ticket.created_at).toLocaleString('vi-VN')}
                                </p>
                            </div>
                            <div>
                                <Label className="text-muted-foreground">
                                    {t('ticket.updated_at', { defaultValue: 'Cập nhật lần cuối' })}
                                </Label>
                                <p className="mt-1">
                                    {new Date(ticket.updated_at).toLocaleString('vi-VN')}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Messages */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('ticket.messages', { defaultValue: 'Tin nhắn' })}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <TicketMessages conversations={ticket.conversations || []} />
                        <div className="border-t pt-6">
                            <h3 className="mb-4 font-semibold">
                                {t('ticket.add_message', { defaultValue: 'Thêm tin nhắn' })}
                            </h3>
                            <SendMessageForm ticketId={ticket.id} />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

