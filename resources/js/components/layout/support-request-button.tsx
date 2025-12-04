import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select as UiSelect, SelectContent as UiSelectContent, SelectItem as UiSelectItem, SelectTrigger as UiSelectTrigger, SelectValue as UiSelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { ticket_store } from '@/routes';
import { _TicketPriority } from '@/pages/ticket/types/constants';
import type { TicketPriority } from '@/pages/ticket/types/type';

export function SupportRequestButton() {
    const { t } = useTranslation();
    const [supportDialogOpen, setSupportDialogOpen] = useState(false);
    const [supportType, setSupportType] = useState<'account_close' | 'account_appeal' | 'transfer_budget' | 'share_bm'>('account_close');
    const [supportNote, setSupportNote] = useState('');
    
    const supportForm = useForm({
        subject: '',
        description: '',
        priority: _TicketPriority.HIGH as TicketPriority,
    });

    const handleSubmitSupportRequest = (e: React.FormEvent) => {
        e.preventDefault();

        const subjectMap: Record<typeof supportType, string> = {
            account_close: t('service_management.support_request_type_account_close'),
            account_appeal: t('service_management.support_request_type_account_appeal'),
            transfer_budget: t('service_management.support_request_type_transfer_budget'),
            share_bm: t('service_management.support_request_type_share_bm'),
        };

        const subject = subjectMap[supportType] || t('service_management.support_request_title');
        const descriptionParts: string[] = [];
        descriptionParts.push(
            `${t('service_management.support_request_type_label')}: ${subject}`,
        );
        if (supportNote.trim()) {
            descriptionParts.push(supportNote.trim());
        }
        const description = descriptionParts.join('\n\n');

        supportForm.setData({
            subject,
            description,
            priority: _TicketPriority.HIGH as TicketPriority,
        });

        supportForm.post(ticket_store().url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('service_management.support_request_success'));
                setSupportDialogOpen(false);
                setSupportNote('');
                setSupportType('account_close');
                supportForm.reset();
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                } else if (errors.subject) {
                    toast.error(Array.isArray(errors.subject) ? errors.subject[0] : errors.subject);
                } else if (errors.description) {
                    toast.error(Array.isArray(errors.description) ? errors.description[0] : errors.description);
                } else {
                    toast.error(t('common.error'));
                }
            },
        });
    };

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                className="bg-white text-primary"
                onClick={() => setSupportDialogOpen(true)}
            >
                {t('service_management.support_request_button')}
            </Button>
            <Dialog open={supportDialogOpen} onOpenChange={setSupportDialogOpen}>
                <DialogContent className="bg-white">
                    <DialogHeader>
                        <DialogTitle>
                            {t('service_management.support_request_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('service_management.support_request_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form className="space-y-4 pt-2" onSubmit={handleSubmitSupportRequest}>
                        <div className="space-y-2">
                            <Label htmlFor="global-support-type">
                                {t('service_management.support_request_type_label')}
                            </Label>
                            <UiSelect
                                value={supportType}
                                onValueChange={(value: any) => setSupportType(value)}
                            >
                                <UiSelectTrigger id="global-support-type">
                                    <UiSelectValue />
                                </UiSelectTrigger>
                                <UiSelectContent>
                                    <UiSelectItem value="account_close">
                                        {t('service_management.support_request_type_account_close')}
                                    </UiSelectItem>
                                    <UiSelectItem value="account_appeal">
                                        {t('service_management.support_request_type_account_appeal')}
                                    </UiSelectItem>
                                    <UiSelectItem value="transfer_budget">
                                        {t('service_management.support_request_type_transfer_budget')}
                                    </UiSelectItem>
                                    <UiSelectItem value="share_bm">
                                        {t('service_management.support_request_type_share_bm')}
                                    </UiSelectItem>
                                </UiSelectContent>
                            </UiSelect>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="global-support-note">
                                {t('service_management.support_request_note_label')}
                            </Label>
                            <Textarea
                                id="global-support-note"
                                value={supportNote}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                                    setSupportNote(e.target.value)
                                }
                                placeholder={t(
                                    'service_management.support_request_note_placeholder',
                                )}
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setSupportDialogOpen(false)}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button 
                                type="submit" 
                                variant="outline" 
                                className="bg-white text-primary"
                                disabled={supportForm.processing}
                            >
                                {supportForm.processing 
                                    ? 'Đang gửi...' 
                                    : t('service_management.support_request_submit')
                                }
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

