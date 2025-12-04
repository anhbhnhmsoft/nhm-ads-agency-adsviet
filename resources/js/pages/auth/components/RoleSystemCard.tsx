import { _RoleSystemRequest } from '@/pages/auth/types/constants';
import { useTranslation } from 'react-i18next';
import { User, Shield } from 'lucide-react';
import { cn } from '@/lib/utils';


type Props = {
    role: _RoleSystemRequest,
    setRole: (role: _RoleSystemRequest) => void
};

const RoleSystemCard = ({role, setRole}: Props) => {
    const { t } = useTranslation();
    return (
       <div className="grid grid-cols-2 gap-2 mb-4">
           <div
               onClick={() => setRole(_RoleSystemRequest.USER)}
               className={cn('rounded-lg border text-card-foreground shadow-sm transition-all duration-200 cursor-pointer hover:bg-[#DAF278] hover:border-green-300 bg-[#DAF278] border-green-200', {
               'bg-[#DAF278] border-[#c3e92d]': role === _RoleSystemRequest.USER
           })}>
               <div className="p-4">
                   <div className="text-center">
                       <User className="h-8 w-8 text-green-600 mx-auto mb-2"/>
                       <h3 className="font-bol">{t('auth.components.customer')}</h3>
                       <p className="hidden sm:block text-xs mt-1">{t('auth.components.customer_desc')}</p>
                   </div>
               </div>
           </div>
           <div
               onClick={() => setRole(_RoleSystemRequest.ADMIN)}
               className={cn('rounded-lg border text-card-foreground shadow-sm transition-all duration-200 cursor-pointer hover:bg-blue-200 hover:border-blue-300 bg-blue-50 border-blue-200', {
               'bg-blue-200 border-blue-300': role === _RoleSystemRequest.ADMIN
           })}>
               <div className="p-4">
                   <div className="text-center">
                       <Shield className="h-8 w-8 text-blue-600 mx-auto mb-2"/>
                       <h3 className="font-bold text-blue-800">{t('auth.components.admin')}</h3>
                       <p className="hidden sm:block text-xs text-blue-600 mt-1">{t('auth.components.admin_desc')}</p>
                   </div>
               </div>
           </div>
       </div>
    );
}

export default RoleSystemCard;
