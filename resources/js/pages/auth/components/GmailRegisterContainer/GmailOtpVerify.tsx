import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import { auth_register_verify_email_otp } from '@/routes';

type Props = {
    email: string;
    onBack: () => void;
};

export default function GmailOtpVerify({ email, onBack }: Props) {
    const { t } = useTranslation();
    const form = useForm({
        email,
        otp: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(auth_register_verify_email_otp().url, {
            preserveScroll: true,
        });
    };

    return (
        <form className="grid gap-3" onSubmit={handleSubmit}>
            <p className="text-sm text-muted-foreground">
                {t('auth.register.otp_sent_hint', { email })}
            </p>
            <label className="text-sm font-medium" htmlFor="register-otp">
                {t('auth.register.enter_email_otp')}
            </label>
            <Input
                id="register-otp"
                value={form.data.otp}
                onChange={(event) => form.setData('otp', event.target.value)}
                placeholder={t('auth.register.enter_email_otp')}
                maxLength={6}
                disabled={form.processing}
                required
            />
            <InputError message={form.errors.otp} />
            <div className="flex gap-2">
                <Button type="button" variant="outline" disabled={form.processing} onClick={onBack}>
                    {t('common.back')}
                </Button>
                <Button type="submit" disabled={form.processing} className="flex-1">
                    {form.processing ? t('auth.register.verifying') : t('auth.register.verify_code')}
                </Button>
            </div>
        </form>
    );
}

