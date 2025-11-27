import { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { profile_change_password } from '@/routes';

const ChangePasswordCard = () => {
    const { t } = useTranslation();

    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });

    const handleChangePassword = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Validate client-side
        if (passwordForm.data.new_password !== passwordForm.data.new_password_confirmation) {
            passwordForm.setError('new_password_confirmation', t('profile.password_confirmation_mismatch'));
            return;
        }

        if (passwordForm.data.new_password.length < 6) {
            passwordForm.setError('new_password', t('common_validation.password.min', { min: 6 }));
            return;
        }

        passwordForm.put(profile_change_password().url, {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('profile.change_password')}</CardTitle>
                <CardDescription>{t('profile.change_password_description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" onSubmit={handleChangePassword}>
                    <div className="space-y-2">
                        <Label htmlFor="current_password">{t('profile.current_password')}</Label>
                        <Input
                            id="current_password"
                            type="password"
                            value={passwordForm.data.current_password}
                            onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                            disabled={passwordForm.processing}
                            aria-invalid={!!passwordForm.errors.current_password}
                        />
                        {passwordForm.errors.current_password && (
                            <p className="text-xs text-destructive">{passwordForm.errors.current_password}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="new_password">{t('profile.new_password')}</Label>
                        <Input
                            id="new_password"
                            type="password"
                            value={passwordForm.data.new_password}
                            onChange={(event) => passwordForm.setData('new_password', event.target.value)}
                            disabled={passwordForm.processing}
                            aria-invalid={!!passwordForm.errors.new_password}
                        />
                        {passwordForm.errors.new_password && (
                            <p className="text-xs text-destructive">{passwordForm.errors.new_password}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="new_password_confirmation">{t('profile.confirm_new_password')}</Label>
                        <Input
                            id="new_password_confirmation"
                            type="password"
                            value={passwordForm.data.new_password_confirmation}
                            onChange={(event) => passwordForm.setData('new_password_confirmation', event.target.value)}
                            disabled={passwordForm.processing}
                            aria-invalid={!!passwordForm.errors.new_password_confirmation}
                        />
                        {passwordForm.errors.new_password_confirmation && (
                            <p className="text-xs text-destructive">{passwordForm.errors.new_password_confirmation}</p>
                        )}
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={passwordForm.processing}>
                            {passwordForm.processing ? t('common.processing') : t('profile.change_password')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

export default ChangePasswordCard;

