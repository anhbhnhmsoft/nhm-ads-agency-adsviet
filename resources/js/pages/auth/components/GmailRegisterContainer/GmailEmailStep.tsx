import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import { auth_register_send_email_otp } from '@/routes';

type Props = {
    onSuccess: (email: string) => void;
};

export default function GmailEmailStep({ onSuccess }: Props) {
    const { t } = useTranslation();
    const form = useForm({
        email: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(auth_register_send_email_otp().url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                onSuccess(form.data.email);
            },
        });
    };

    return (
        <form className="grid gap-3" onSubmit={handleSubmit}>
            <label className="text-sm font-medium" htmlFor="register-email">
                {t('auth.register.email_gmail')}
            </label>
            <Input
                id="register-email"
                type="email"
                value={form.data.email}
                onChange={(event) => form.setData('email', event.target.value)}
                placeholder={t('auth.register.enter_email_gmail')}
                disabled={form.processing}
                required
            />
            <InputError message={form.errors.email} />
            <Button type="submit" disabled={form.processing}>
                {form.processing ? t('auth.register.sending') : t('auth.register.send_code')}
            </Button>
        </form>
    );
}

