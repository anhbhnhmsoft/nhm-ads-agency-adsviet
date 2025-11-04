import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import { CustomerListItem } from '@/pages/user/types/type';
import { userRolesLabel } from '@/lib/types/constants';
import { Check, OctagonX } from 'lucide-react';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: CustomerListItem | null;
};

export default function UserInfoDialog({ open, onOpenChange, user }: Props) {
    const { t } = useTranslation();

    if (!user) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{t('user.customer_info')}</DialogTitle>
                </DialogHeader>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.name')}</label>
                        <div className="text-sm">{user.name}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.username')}</label>
                        <div className="text-sm">{user.username}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.phone')}</label>
                        <div className="text-sm">{user.phone || '-'}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.role')}</label>
                        <div className="text-sm">{t(userRolesLabel[user.role])}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.account_active')}</label>
                        <div className="flex items-center gap-2">
                            {!user.disabled ? (
                                <>
                                    <Check className="size-4 text-green-500" />
                                    <span className="text-sm">{t('common.active')}</span>
                                </>
                            ) : (
                                <>
                                    <OctagonX className="size-4 text-red-500" />
                                    <span className="text-sm">{t('common.disabled')}</span>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.referral_code')}</label>
                        <div className="text-sm">{user.referral_code}</div>
                    </div>
                    <div className="grid gap-1">
                        <label className="text-sm font-medium text-gray-500">{t('common.social_authentication')}</label>
                        <div className="flex flex-col gap-1">
                            {user.using_telegram && (
                                <div className="text-sm">{t('common.using_telegram')}</div>
                            )}
                            {user.using_whatsapp && (
                                <div className="text-sm">{t('common.using_whatsapp')}</div>
                            )}
                            {!user.using_telegram && !user.using_whatsapp && (
                                <div className="text-sm text-gray-400">-</div>
                            )}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
