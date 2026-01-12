import { _UserRole } from '@/lib/types/constants';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import { ShieldCheck, User } from 'lucide-react';

type Props = {
    role: _UserRole.CUSTOMER | _UserRole.AGENCY,
    setRole: (role: _UserRole.CUSTOMER | _UserRole.AGENCY) => void
};
const CustomerRoleCard = ({ role, setRole }: Props) => {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-2 gap-2">
            <div
                onClick={() => setRole(_UserRole.CUSTOMER)}
                className={cn('rounded-lg border text-card-foreground shadow-sm transition-all duration-200 cursor-pointer hover:bg-green-200 hover:border-green-300 bg-green-50 border-green-200', {
                    'bg-green-200 border-green-300': role === _UserRole.CUSTOMER
                })}>
                <div className="p-4">
                    <div className="text-center">
                        <User className="h-8 w-8 text-green-600 mx-auto mb-2"/>
                        <h3 className="font-bold text-green-800">{t('auth.components.customer')}</h3>
                        <p className="hidden sm:block text-xs text-green-600 mt-1">{t('auth.components.customer_desc')}</p>
                    </div>
                </div>
            </div>
            <div
                onClick={() => setRole(_UserRole.AGENCY)}
                className={cn('rounded-lg border text-card-foreground shadow-sm transition-all duration-200 cursor-pointer hover:bg-orange-200 hover:border-orange-300 bg-orange-50 border-orange-200', {
                    'bg-orange-200 border-orange-300': role === _UserRole.AGENCY
                })}>
                <div className="p-4">
                    <div className="text-center">
                        <ShieldCheck className="h-8 w-8 text-[#4285f4] mx-auto mb-2"/>
                        <h3 className="font-bold text-[#4285f4]">{t('auth.components.agency')}</h3>
                        <p className="hidden sm:block text-xs text-[#4285f4] mt-1">{t('auth.components.agency_desc')}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default CustomerRoleCard;
