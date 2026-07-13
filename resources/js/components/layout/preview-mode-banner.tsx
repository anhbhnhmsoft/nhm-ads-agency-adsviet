import { Button } from '@/components/ui/button';
import { _UserRole } from '@/lib/types/constants';
import { IPreviewContext, IUser } from '@/lib/types/type';
import { admin_preview_stop } from '@/routes';
import { router } from '@inertiajs/react';
import { ArrowLeft, ShieldAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';

type Props = {
    actor: IUser | null;
    preview: IPreviewContext | null;
};

export function PreviewModeBanner({ actor, preview }: Props) {
    const { t } = useTranslation();

    if (!preview?.is_active || !preview.target) {
        return null;
    }

    const accountTypeLabel = (() => {
        switch (preview.target.role) {
            case _UserRole.EMPLOYEE:
                return t('user.preview.account_types.employee');
            case _UserRole.MANAGER:
                return t('user.preview.account_types.manager');
            case _UserRole.AGENCY:
                return t('user.preview.account_types.agency');
            case _UserRole.CUSTOMER:
            default:
                return t('user.preview.account_types.customer');
        }
    })();

    return (
        <div className="flex min-h-[100px] items-center border-b border-amber-300 bg-amber-50 px-4 text-amber-950 md:px-6">
            <div className="flex w-full flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex items-start gap-3">
                    <div className="rounded-full bg-amber-100 p-2 text-amber-700">
                        <ShieldAlert className="size-5" />
                    </div>
                    <div className="space-y-1">
                        <div className="text-base font-semibold">
                            {t('user.preview.banner_title', {
                                type: accountTypeLabel,
                            })}
                        </div>
                        <div className="text-sm">
                            {t('user.preview.current_account')}:{' '}
                            <span className="font-semibold">
                                {preview.target.name}
                            </span>
                            {preview.target.username
                                ? ` (${preview.target.username})`
                                : ''}
                        </div>
                        <div className="text-xs text-amber-800">
                            {t('user.preview.original_login')}:{' '}
                            {actor?.name ?? t('user.preview.admin_fallback')}
                            {actor?.username ? ` (${actor.username})` : ''}
                        </div>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100"
                        onClick={() =>
                            router.post(
                                admin_preview_stop().url,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <ArrowLeft className="mr-2 size-4" />
                        {t('user.preview.back_to_admin')}
                    </Button>
                </div>
            </div>
        </div>
    );
}
