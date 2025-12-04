import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { IBreadcrumbItem } from '@/lib/types/type';
import { ticket_index, ticket_show, ticket_store } from '@/routes';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MessageSquare, Plus, Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { TicketPageProps, Ticket, TicketStatus, TicketPriority } from './types/type';
import { _TicketStatus, _TicketPriority } from './types/constants';
import { useState, useMemo } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';

export default function TicketIndex({ tickets, error }: TicketPageProps) {
    const { t, i18n } = useTranslation();
    const { props } = usePage();
    const authUser = useMemo(() => {
        const authProp = props.auth as { user?: any } | any | null | undefined;
        if (authProp && typeof authProp === 'object' && 'user' in authProp) {
            return authProp.user ?? null;
        }
        return (authProp as any | null) ?? null;
    }, [props.auth]);
    const checkRole = useCheckRole(authUser);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const isCustomerOrAgency = checkRole([_UserRole.CUSTOMER, _UserRole.AGENCY]);
    
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const createForm = useForm({
        subject: '',
        description: '',
        priority: _TicketPriority.MEDIUM,
    });

    const handleCreateTicket = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(ticket_store().url, {
            preserveScroll: true,
            onSuccess: () => {
                setShowCreateDialog(false);
                createForm.reset();
            },
        });
    };

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

    const filteredTickets = tickets?.data.filter((ticket) =>
        ticket.subject.toLowerCase().includes(searchQuery.toLowerCase()) ||
        ticket.description.toLowerCase().includes(searchQuery.toLowerCase())
    ) || [];

    const breadcrumbs: IBreadcrumbItem[] = [
        {
            title: t('ticket.title', { defaultValue: 'Support' }),
            href: ticket_index().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('ticket.title', { defaultValue: 'Hỗ trợ' })} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('ticket.title', { defaultValue: 'Hỗ trợ' })}</h1>
                        <p className="text-muted-foreground">
                            {t('ticket.list', { defaultValue: 'Danh sách yêu cầu hỗ trợ' })}
                        </p>
                    </div>
                    {!isAdmin && (
                        <Button onClick={() => setShowCreateDialog(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            {t('ticket.create', { defaultValue: 'Tạo yêu cầu hỗ trợ' })}
                        </Button>
                    )}
                </div>

                {error && (
                    <Card className="border-red-500 bg-red-50 dark:bg-red-950/20">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-2 text-red-600">{error}</div>
                        </CardContent>
                    </Card>
                )}

                {/* Search */}
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder={t('common.search', { defaultValue: 'Tìm kiếm...' })}
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9"
                    />
                </div>

                {/* Tickets List */}
                {filteredTickets.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                {t('ticket.no_tickets', { defaultValue: 'Chưa có yêu cầu hỗ trợ nào' })}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {filteredTickets.map((ticket: Ticket) => (
                            <Card key={ticket.id} className="cursor-pointer transition-colors hover:bg-muted/50">
                                <Link href={ticket_show({ id: ticket.id }).url}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <CardTitle className="mb-2">{ticket.subject}</CardTitle>
                                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                                    {ticket.description}
                                                </p>
                                            </div>
                                            <div className="ml-4 flex gap-2">
                                                {getStatusBadge(ticket.status)}
                                                {getPriorityBadge(ticket.priority)}
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                                            <div className="flex items-center gap-4">
                                                <span>
                                                    {t('ticket.created_by', { defaultValue: 'Người tạo' })}:{' '}
                                                    {ticket.user?.name || ticket.user_id}
                                                </span>
                                                {ticket.assignedUser && (
                                                    <span>
                                                        {t('ticket.assigned_to', { defaultValue: 'Người xử lý' })}:{' '}
                                                        {ticket.assignedUser.name}
                                                    </span>
                                                )}
                                            </div>
                                            <span>
                                                {new Date(ticket.created_at).toLocaleDateString(i18n.language || 'en', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                        </div>
                                    </CardContent>
                                </Link>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {tickets && tickets.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            {t('common.showing', {
                                defaultValue: 'Hiển thị {{from}} đến {{to}} trong tổng số {{total}}',
                                from: tickets.from ?? 0,
                                to: tickets.to ?? 0,
                                total: tickets.total,
                            })}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={tickets.current_page === 1}
                                onClick={() =>
                                    router.get(
                                        ticket_index().url,
                                        { page: tickets.current_page - 1 },
                                        { preserveScroll: true, preserveState: true }
                                    )
                                }
                            >
                                {t('common.previous', { defaultValue: 'Trước' })}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={tickets.current_page === tickets.last_page}
                                onClick={() =>
                                    router.get(
                                        ticket_index().url,
                                        { page: tickets.current_page + 1 },
                                        { preserveScroll: true, preserveState: true }
                                    )
                                }
                            >
                                {t('common.next', { defaultValue: 'Sau' })}
                            </Button>
                        </div>
                    </div>
                )}

                {/* Create Ticket Dialog */}
                <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>{t('ticket.create', { defaultValue: 'Tạo yêu cầu hỗ trợ' })}</DialogTitle>
                            <DialogDescription>
                                {t('ticket.create_description', {
                                    defaultValue: 'Mô tả vấn đề của bạn để nhận được hỗ trợ',
                                })}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCreateTicket} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="subject">
                                    {t('ticket.subject', { defaultValue: 'Chủ đề' })} <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="subject"
                                    value={createForm.data.subject}
                                    onChange={(e) => createForm.setData('subject', e.target.value)}
                                    placeholder={t('ticket.subject_placeholder', { defaultValue: 'Nhập chủ đề...' })}
                                    required
                                />
                                {createForm.errors.subject && (
                                    <p className="text-sm text-red-500">{createForm.errors.subject}</p>
                                )}
                            </div>
                            {!isCustomerOrAgency && (
                                <div className="space-y-2">
                                    <Label htmlFor="priority">
                                        {t('ticket.priority_label', { defaultValue: 'Mức độ ưu tiên' })}
                                    </Label>
                                    <Select
                                        value={createForm.data.priority.toString()}
                                        onValueChange={(value) => createForm.setData('priority', parseInt(value))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={_TicketPriority.LOW.toString()}>{t('ticket.priority.low')}</SelectItem>
                                        <SelectItem value={_TicketPriority.MEDIUM.toString()}>{t('ticket.priority.medium')}</SelectItem>
                                        <SelectItem value={_TicketPriority.HIGH.toString()}>{t('ticket.priority.high')}</SelectItem>
                                        <SelectItem value={_TicketPriority.URGENT.toString()}>{t('ticket.priority.urgent')}</SelectItem>
                                    </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="description">
                                    {t('ticket.description', { defaultValue: 'Mô tả' })} <span className="text-red-500">*</span>
                                </Label>
                                <Textarea
                                    id="description"
                                    value={createForm.data.description}
                                    onChange={(e) => createForm.setData('description', e.target.value)}
                                    placeholder={t('ticket.description_placeholder', {
                                        defaultValue: 'Mô tả chi tiết vấn đề của bạn...',
                                    })}
                                    rows={6}
                                    required
                                />
                                {createForm.errors.description && (
                                    <p className="text-sm text-red-500">{createForm.errors.description}</p>
                                )}
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowCreateDialog(false)}
                                >
                                    {t('common.cancel', { defaultValue: 'Hủy' })}
                                </Button>
                                <Button type="submit" disabled={createForm.processing}>
                                    {createForm.processing
                                        ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                                        : t('common.submit', { defaultValue: 'Gửi' })}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}

