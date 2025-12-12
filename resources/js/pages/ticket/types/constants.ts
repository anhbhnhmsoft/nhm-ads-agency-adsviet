export enum _TicketStatus {
    PENDING = 0,
    OPEN = 1,
    IN_PROGRESS = 2,
    RESOLVED = 3,
    CLOSED = 4,
}

export enum _TicketPriority {
    LOW = 0,
    MEDIUM = 1,
    HIGH = 2,
    URGENT = 3,
}

export enum _TicketReplySide {
    CUSTOMER = 0,
    AGENT = 1,
    SYSTEM = 2,
}

export enum _TicketType {
    TRANSFER_BUDGET = 'transfer_budget',
    ACCOUNT_LIQUIDATION = 'account_liquidation',
    ACCOUNT_APPEAL = 'account_appeal',
    SHARE_BM = 'share_bm',
    WALLET_WITHDRAW_APP = 'wallet_withdraw_app',
    WALLET_DEPOSIT_APP = 'wallet_deposit_app',
}

export const ticketStatusLabel: Record<_TicketStatus, string> = {
    [_TicketStatus.PENDING]: 'ticket.status.pending',
    [_TicketStatus.OPEN]: 'ticket.status.open',
    [_TicketStatus.IN_PROGRESS]: 'ticket.status.in_progress',
    [_TicketStatus.RESOLVED]: 'ticket.status.resolved',
    [_TicketStatus.CLOSED]: 'ticket.status.closed',
};

export const ticketPriorityLabel: Record<_TicketPriority, string> = {
    [_TicketPriority.LOW]: 'ticket.priority.low',
    [_TicketPriority.MEDIUM]: 'ticket.priority.medium',
    [_TicketPriority.HIGH]: 'ticket.priority.high',
    [_TicketPriority.URGENT]: 'ticket.priority.urgent',
};

export const ticketReplySideLabel: Record<_TicketReplySide, string> = {
    [_TicketReplySide.CUSTOMER]: 'ticket.reply_side.customer',
    [_TicketReplySide.AGENT]: 'ticket.reply_side.staff',
    [_TicketReplySide.SYSTEM]: 'ticket.reply_side.system',
};

export const ticketTypeLabel: Record<_TicketType, string> = {
    [_TicketType.TRANSFER_BUDGET]: 'ticket.type.transfer_budget',
    [_TicketType.ACCOUNT_LIQUIDATION]: 'ticket.type.account_liquidation',
    [_TicketType.ACCOUNT_APPEAL]: 'ticket.type.account_appeal',
    [_TicketType.SHARE_BM]: 'ticket.type.share_bm',
    [_TicketType.WALLET_WITHDRAW_APP]: 'ticket.type.wallet_withdraw_app',
    [_TicketType.WALLET_DEPOSIT_APP]: 'ticket.type.wallet_deposit_app',
};

