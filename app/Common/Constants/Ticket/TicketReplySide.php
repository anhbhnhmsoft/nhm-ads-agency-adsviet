<?php

namespace App\Common\Constants\Ticket;

enum TicketReplySide: int
{
    case CUSTOMER = 0;
    case AGENT = 1;
    case SYSTEM = 2;

    public function label(): string
    {
        return match ($this) {
            TicketReplySide::CUSTOMER => __('ticket.reply_side.customer'),
            TicketReplySide::AGENT => __('ticket.reply_side.staff'),
            TicketReplySide::SYSTEM => __('ticket.reply_side.system'),
        };
    }
}

