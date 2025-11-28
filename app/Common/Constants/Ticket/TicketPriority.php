<?php

namespace App\Common\Constants\Ticket;

enum TicketPriority: int
{
    case LOW = 0;
    case MEDIUM = 1;
    case HIGH = 2;
    case URGENT = 3;

    public function label(): string
    {
        return match ($this) {
            TicketPriority::LOW => __('ticket.priority.low'),
            TicketPriority::MEDIUM => __('ticket.priority.medium'),
            TicketPriority::HIGH => __('ticket.priority.high'),
            TicketPriority::URGENT => __('ticket.priority.urgent'),
        };
    }
}

