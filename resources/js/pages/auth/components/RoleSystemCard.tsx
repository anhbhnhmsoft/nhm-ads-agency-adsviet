import { cn } from '@/lib/utils';
import { _RoleSystemRequest } from '@/pages/auth/types/constants';
import { Shield, User } from 'lucide-react';
import { useTranslation } from 'react-i18next';

type Props = {
    role: _RoleSystemRequest;
    setRole: (role: _RoleSystemRequest) => void;
};

const RoleSystemCard = ({ role, setRole }: Props) => {
    const { t } = useTranslation();
    return (
        <div className="mb-4 grid grid-cols-2 gap-2">
            <div
                onClick={() => setRole(_RoleSystemRequest.USER)}
                className={cn(
                    'cursor-pointer rounded-lg border border-green-200 bg-[#d5e78d] text-card-foreground shadow-sm transition-all duration-200 hover:border-green-300 hover:bg-[#ecfda8]',
                    {
                        'border-[#c3e92d] bg-[#DAF278]':
                            role === _RoleSystemRequest.USER,
                    },
                )}
            >
                <div className="p-4">
                    <div className="text-center">
                        <User className="mx-auto mb-2 h-8 w-8 text-green-600" />
                        <h3 className="font-bol">
                            {t('auth.components.customer')}
                        </h3>
                        <p className="mt-1 hidden text-xs sm:block">
                            {t('auth.components.customer_desc')}
                        </p>
                    </div>
                </div>
            </div>
            <div
                onClick={() => setRole(_RoleSystemRequest.ADMIN)}
                className={cn(
                    'cursor-pointer rounded-lg border border-orange-200 bg-orange-50 text-card-foreground shadow-sm transition-all duration-200 hover:border-orange-300 hover:bg-orange-200',
                    {
                        'border-orange-300 bg-orange-200':
                            role === _RoleSystemRequest.ADMIN,
                    },
                )}
            >
                <div className="p-4">
                    <div className="text-center">
                        <Shield className="mx-auto mb-2 h-8 w-8 text-[#eb4e23]" />
                        <h3 className="font-bold text-[#eb4e23]">
                            {t('auth.components.admin')}
                        </h3>
                        <p className="mt-1 hidden text-xs text-[#eb4e23] sm:block">
                            {t('auth.components.admin_desc')}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default RoleSystemCard;
