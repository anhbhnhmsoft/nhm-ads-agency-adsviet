import type { AccountOption } from '../../components/PlatformAccountSelector';
import type { Ticket } from '../../types/type';
import type { LaravelPaginator } from '@/lib/types/type';

export type RefundAccount = AccountOption;

export type RefundPageProps = {
    tickets: LaravelPaginator<Ticket> | null;
    accounts: RefundAccount[];
    error: string | null;
};

export type RefundFormProps = {
    accounts: RefundAccount[];
};

