import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { auth_register_send_email_otp } from '@/routes';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';

export default function GmailEmailStep() {
    const { t } = useTranslation();
    const form = useForm({
        email: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(auth_register_send_email_otp().url, {
            preserveScroll: true,
            preserveState: true,
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
                {form.processing
                    ? t('auth.register.sending')
                    : t('auth.register.continue_with_gmail')}
            </Button>
        </form>
    );
}
