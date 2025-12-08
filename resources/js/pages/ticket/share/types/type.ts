import type { AccountOption } from '../../components/PlatformAccountSelector';
import type { Ticket } from '../../types/type';
import type { LaravelPaginator } from '@/lib/types/type';

export type ShareAccount = AccountOption;

export type SharePageProps = {
    tickets: LaravelPaginator<Ticket> | null;
    accounts: ShareAccount[];
    error: string | null;
};

export type ShareFormProps = {
    accounts: ShareAccount[];
};

