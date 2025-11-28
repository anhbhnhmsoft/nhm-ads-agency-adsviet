<?php

namespace App\Common\Constants\Ticket;

enum TicketStatus: int
{
    case PENDING = 0;
    case OPEN = 1;
    case IN_PROGRESS = 2;
    case RESOLVED = 3;
    case CLOSED = 4;

    public function label(): string
    {
        return match ($this) {
            TicketStatus::PENDING => __('ticket.status.pending'),
            TicketStatus::OPEN => __('ticket.status.open'),
            TicketStatus::IN_PROGRESS => __('ticket.status.in_progress'),
            TicketStatus::RESOLVED => __('ticket.status.resolved'),
            TicketStatus::CLOSED => __('ticket.status.closed'),
        };
    }
}

