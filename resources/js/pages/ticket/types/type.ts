import { IUser } from '@/lib/types/type';
import { _TicketStatus, _TicketPriority, _TicketReplySide, _TicketType } from './constants';
import type { TransferAccount } from '@/pages/ticket/transfer/types/type';

export type TicketStatus = _TicketStatus;
export type TicketPriority = _TicketPriority;
export type TicketReplySide = _TicketReplySide;
export type TicketType = _TicketType;

export type Ticket = {
    id: string;
    user_id: string;
    subject: string;
    description: string;
    status: TicketStatus;
    priority: TicketPriority;
    assigned_to: string | null;
    type?: TicketType | null;
    metadata?: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    user?: IUser;
    assignedUser?: IUser | null;
    conversations?: TicketConversation[];
};

export type TicketConversation = {
    id: string;
    ticket_id: string;
    user_id: string;
    message: string;
    attachment: string | null;
    reply_side: TicketReplySide;
    created_at: string;
    updated_at: string;
    user?: IUser;
};

export type TicketPageProps = {
    tickets: {
        data: Ticket[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    } | null;
    error: string | null;
};

export type TicketDetailPageProps = {
    ticket: Ticket | null;
};

export type TransferPageProps = {
    tickets: {
        data: Ticket[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    } | null;
    accounts: TransferAccount[];
    error: string | null;
};

