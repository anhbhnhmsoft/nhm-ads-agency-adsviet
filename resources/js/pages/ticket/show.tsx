import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { IBreadcrumbItem } from '@/lib/types/type';
import { ticket_index, ticket_update_status } from '@/routes';
import { Head, Link, usePage, usePoll, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { TicketDetailPageProps, TicketStatus, TicketPriority } from './types/type';
import { _TicketStatus, _TicketPriority } from './types/constants';
import { TicketMessages } from './components/TicketMessages';
import { SendMessageForm } from './components/SendMessageForm';
import useCheckRole from '@/hooks/use-check-role';
import { _UserRole } from '@/lib/types/constants';
import { useMemo, useEffect, useState } from 'react';
import { _PlatformType } from '@/lib/types/constants';

export default function TicketShow({ ticket }: TicketDetailPageProps) {
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

    const breadcrumbs: IBreadcrumbItem[] = [
        {
            title: t('ticket.title', { defaultValue: 'Support' }),
            href: ticket_index().url,
        },
    ];

    const [currentStatus, setCurrentStatus] = useState<TicketStatus>(
        ticket?.status ?? _TicketStatus.PENDING
    );
    const [statusProcessing, setStatusProcessing] = useState(false);

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

    const getSubjectLabel = (subject: string) => {
        const map: Record<string, string> = {
            transfer_request: t('ticket.transfer.title', { defaultValue: 'Chuyển tiền giữa các tài khoản' }),
            refund_request: t('ticket.refund.title', { defaultValue: 'Thanh lý tài khoản' }),
            appeal_request: t('ticket.appeal.title', { defaultValue: 'Kháng tài khoản' }),
            share_request: t('ticket.share.title', { defaultValue: 'Share BM/MCC' }),
        };
        return map[subject] ?? subject;
    };

    const metadata: any = ticket.metadata || {};
    const metadataType = metadata.type;
    const shouldHideDescription = metadataType === 'wallet_withdraw_app' || metadataType === 'wallet_deposit_app';

    const getPlatformName = (platform?: number) => {
        if (platform === _PlatformType.GOOGLE) return t('enum.platform_type.google', { defaultValue: 'Google Ads' });
        if (platform === _PlatformType.META) return t('enum.platform_type.meta', { defaultValue: 'Meta Ads' });
        return '-';
    };

    const renderMetadata = () => {
        const type = metadataType;

        if (type === 'transfer') {
            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.transfer.platform', { defaultValue: 'Kênh quảng cáo' })}: {getPlatformName(metadata.platform)}</div>
                    <div>{t('ticket.transfer.from_account', { defaultValue: 'Từ tài khoản' })}: {metadata.from_account_name ? `${metadata.from_account_name} (${metadata.from_account_id})` : metadata.from_account_id || '-'}</div>
                    <div>{t('ticket.transfer.to_account', { defaultValue: 'Đến tài khoản' })}: {metadata.to_account_name ? `${metadata.to_account_name} (${metadata.to_account_id})` : metadata.to_account_id || '-'}</div>
                    <div>{t('ticket.transfer.amount', { defaultValue: 'Số tiền' })}: {metadata.amount ? `${parseFloat(metadata.amount).toFixed(2)} ${metadata.currency || 'USD'}` : '-'}</div>
                    <div>{t('ticket.transfer.notes', { defaultValue: 'Ghi chú' })}: {metadata.notes || '-'}</div>
                </div>
            );
        }

        if (type === 'refund') {
            const accountIds = metadata.account_ids || [];
            const accountNames = metadata.account_names || [];
            const accountsText = accountIds.map((id: string, idx: number) => {
                const name = accountNames[idx] || '';
                return name ? `${name} (${id})` : id;
            }).join(', ');

            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.refund.platform', { defaultValue: 'Kênh quảng cáo' })}: {getPlatformName(metadata.platform)}</div>
                    <div>{t('ticket.refund.accounts', { defaultValue: 'Tài khoản thanh lý' })}: {accountsText || '-'}</div>
                    <div>{t('ticket.refund.liquidation_type', { defaultValue: 'Loại thanh lý' })}: {metadata.liquidation_type === 'withdraw_to_wallet' ? t('ticket.refund.withdraw_to_wallet', { defaultValue: 'Rút Tiền Về Ví' }) : metadata.liquidation_type || '-'}</div>
                    <div>{t('ticket.refund.notes', { defaultValue: 'Ghi chú' })}: {metadata.notes || '-'}</div>
                </div>
            );
        }

        if (type === 'appeal') {
            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.appeal.platform', { defaultValue: 'Kênh quảng cáo' })}: {getPlatformName(metadata.platform)}</div>
                    <div>{t('ticket.appeal.account', { defaultValue: 'Tài khoản cần kháng' })}: {metadata.account_name ? `${metadata.account_name} (${metadata.account_id})` : metadata.account_id || '-'}</div>
                    <div>{t('ticket.appeal.notes', { defaultValue: 'Ghi chú' })}: {metadata.notes || '-'}</div>
                </div>
            );
        }

        if (type === 'share') {
            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.share.platform', { defaultValue: 'Kênh quảng cáo' })}: {getPlatformName(metadata.platform)}</div>
                    <div>{t('ticket.share.account', { defaultValue: 'Tài khoản' })}: {metadata.account_name ? `${metadata.account_name} (${metadata.account_id})` : metadata.account_id || '-'}</div>
                    <div>{t('ticket.share.bm_bc_mcc_id', { defaultValue: 'ID BM/MCC' })}: {metadata.bm_bc_mcc_id || '-'}</div>
                    <div>{t('ticket.share.notes', { defaultValue: 'Ghi chú' })}: {metadata.notes || '-'}</div>
                </div>
            );
        }

        if (type === 'wallet_withdraw_app') {
            const withdrawType = metadata.withdraw_type === 'usdt' ? 'usdt' : 'bank';
            const amountText = metadata.amount ? `${parseFloat(metadata.amount).toFixed(2)} USDT` : '-';
            const noteText = metadata.notes || ticket.description || '-';
            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.withdraw_app.amount_label')}: {amountText}</div>
                    <div>
                        {t('ticket.withdraw_app.method_label')}: {withdrawType === 'bank' ? t('wallet.withdraw_via_bank') : t('wallet.withdraw_via_usdt')}
                    </div>
                    {withdrawType === 'bank' ? (
                        <>
                            <div>{t('wallet.bank_name')}: {metadata.withdraw_info?.bank_name || '-'}</div>
                            <div>{t('wallet.account_holder')}: {metadata.withdraw_info?.account_holder || '-'}</div>
                            <div>{t('wallet.account_number')}: {metadata.withdraw_info?.account_number || '-'}</div>
                        </>
                    ) : (
                        <>
                            <div>{t('wallet.crypto_address')}: {metadata.withdraw_info?.crypto_address || '-'}</div>
                            <div>{t('wallet.select_network')}: {metadata.withdraw_info?.network || '-'}</div>
                        </>
                    )}
                    <div>{t('ticket.withdraw_app.note_label')}: {noteText}</div>
                </div>
            );
        }

        if (type === 'wallet_deposit_app') {
            const amountText = metadata.amount ? `${parseFloat(metadata.amount).toFixed(2)} USD` : '-';
            const platformText = getPlatformName(metadata.platform);
            const accountText = metadata.account_name 
                ? `${metadata.account_name} (${metadata.account_id})` 
                : metadata.account_id || '-';
            const noteText = metadata.notes || ticket.description || '-';
            return (
                <div className="grid gap-2 text-sm">
                    <div>{t('ticket.transfer.platform', { defaultValue: 'Kênh quảng cáo' })}: {platformText}</div>
                    <div>{t('ticket.deposit_app.account_label', { defaultValue: 'Tài khoản cần nạp tiền' })}: {accountText}</div>
                    <div>{t('ticket.deposit_app.amount_label', { defaultValue: 'Số tiền nạp' })}: {amountText}</div>
                    {noteText && noteText !== '-' && (
                        <div>{t('ticket.deposit_app.note_label', { defaultValue: 'Ghi chú (tùy chọn)' })}: {noteText}</div>
                    )}
                </div>
            );
        }

        return null;
    };

    const handleStatusChange = (newStatus: string) => {
        const parsedStatus = parseInt(newStatus) as TicketStatus;
        setStatusProcessing(true);
        router.put(
            ticket_update_status({ id: ticket.id }).url,
            { status: parsedStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCurrentStatus(parsedStatus);
                },
                onFinish: () => {
                    setStatusProcessing(false);
                },
            }
        );
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
                        <h1 className="text-2xl font-bold">{getSubjectLabel(ticket.subject)}</h1>
                        <div className="mt-2 flex items-center gap-2">
                            {getStatusBadge(currentStatus)}
                            {getPriorityBadge(ticket.priority)}
                        </div>
                    </div>
                    {canUpdateStatus && (
                        <div className="flex items-center gap-2">
                            <Label>{t('ticket.status_label', { defaultValue: 'Trạng thái' })}</Label>
                            <Select
                                value={currentStatus.toString()}
                                onValueChange={handleStatusChange}
                                disabled={statusProcessing}
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
                {!shouldHideDescription && (
                    <div>
                        <Label className="text-muted-foreground">
                            {t('ticket.description', { defaultValue: 'Mô tả' })}
                        </Label>
                        <p className="mt-1 whitespace-pre-wrap">{ticket.description}</p>
                    </div>
                )}
                        {renderMetadata() && (
                            <div>
                                <Label className="text-muted-foreground">
                                    {t('ticket.detail', { defaultValue: 'Chi tiết yêu cầu' })}
                                </Label>
                                <div className="mt-2 rounded-md bg-muted/50 p-3">
                                    {renderMetadata()}
                                </div>
                            </div>
                        )}
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
                                    {new Date(ticket.created_at).toLocaleString(i18n.language || 'en')}
                                </p>
                            </div>
                            <div>
                                <Label className="text-muted-foreground">
                                    {t('ticket.updated_at', { defaultValue: 'Cập nhật lần cuối' })}
                                </Label>
                                <p className="mt-1">
                                    {new Date(ticket.updated_at).toLocaleString(i18n.language || 'en')}
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

