import type { AccountOption } from '../../components/PlatformAccountSelector';
import type { Ticket } from '../../types/type';
import type { LaravelPaginator } from '@/lib/types/type';

export type AppealAccount = AccountOption;

export type AppealPageProps = {
    tickets: LaravelPaginator<Ticket> | null;
    accounts: AppealAccount[];
    adminEmail: string | null;
    error: string | null;
};

export type AppealFormProps = {
    accounts: AppealAccount[];
    adminEmail: string | null;
};

