import { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { profile_update, profile_resend_email } from '@/routes';
import { router } from '@inertiajs/react';
import type { ProfileUser } from '../types/type';
import EmailOtpForm from './EmailOtpForm';

type Props = {
    user: ProfileUser;
    emailOtpSent: boolean;
    onOtpSentChange: (sent: boolean) => void;
};

const BasicInfoCard = ({ user, emailOtpSent, onOtpSentChange }: Props) => {
    const { t } = useTranslation();

    const form = useForm({
        name: user.name ?? '',
        username: user.username ?? '',
        email: user.email ?? '',
        phone: user.phone ?? '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(profile_update().url);
    };

    const handleResendEmail = () => {
        onOtpSentChange(true);
        router.post(profile_resend_email().url, undefined, {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('profile.basic_info')}</CardTitle>
                <CardDescription>{t('profile.basic_info_description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" onSubmit={handleSubmit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">{t('common.name')}</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                disabled={form.processing}
                                aria-invalid={!!form.errors.name}
                            />
                            {form.errors.name && (
                                <p className="text-xs text-destructive">{form.errors.name}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="username">{t('common.username')}</Label>
                            <Input
                                id="username"
                                value={form.data.username}
                                onChange={(event) => form.setData('username', event.target.value)}
                                disabled={form.processing}
                                aria-invalid={!!form.errors.username}
                            />
                            {form.errors.username && (
                                <p className="text-xs text-destructive">{form.errors.username}</p>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="email">{t('common.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                disabled={form.processing}
                                aria-invalid={!!form.errors.email}
                            />
                            {form.errors.email && (
                                <p className="text-xs text-destructive">{form.errors.email}</p>
                            )}
                            {user.email && !user.email_verified_at && (
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    className="mt-2"
                                    disabled={form.processing}
                                    onClick={handleResendEmail}
                                >
                                    {t('profile.resend_email_button')}
                                </Button>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">{t('common.phone')}</Label>
                            <Input
                                id="phone"
                                value={form.data.phone ?? ''}
                                onChange={(event) => form.setData('phone', event.target.value)}
                                disabled={form.processing}
                                aria-invalid={!!form.errors.phone}
                            />
                            {form.errors.phone && (
                                <p className="text-xs text-destructive">{form.errors.phone}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            {t('common.save_changes')}
                        </Button>
                    </div>
                </form>
                {user.email && !user.email_verified_at && emailOtpSent && (
                    <EmailOtpForm user={user} onVerified={() => onOtpSentChange(false)} />
                )}
            </CardContent>
        </Card>
    );
};

export default BasicInfoCard;

