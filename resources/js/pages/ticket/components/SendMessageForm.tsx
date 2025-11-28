import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from 'react-i18next';
import { useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { ticket_add_message } from '@/routes';

interface SendMessageFormProps {
    ticketId: string;
}

export function SendMessageForm({ ticketId }: SendMessageFormProps) {
    const { t } = useTranslation();
    const form = useForm({
        message: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(ticket_add_message({ id: ticketId }).url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
                <Textarea
                    value={form.data.message}
                    onChange={(e) => form.setData('message', e.target.value)}
                    placeholder={t('ticket.message_placeholder', { defaultValue: 'Nhập tin nhắn của bạn...' })}
                    rows={4}
                    required
                />
                {form.errors.message && (
                    <p className="text-sm text-red-500">{form.errors.message}</p>
                )}
            </div>
            <div className="flex justify-end">
                <Button type="submit" disabled={form.processing || !form.data.message.trim()}>
                    <Send className="mr-2 h-4 w-4" />
                    {form.processing
                        ? t('common.processing', { defaultValue: 'Đang xử lý...' })
                        : t('ticket.send', { defaultValue: 'Gửi' })}
                </Button>
            </div>
        </form>
    );
}

