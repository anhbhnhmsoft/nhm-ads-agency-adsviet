<?php

namespace App\Http\Requests\Ticket;

use App\Common\Constants\Ticket\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', 'in:' . implode(',', array_column(TicketStatus::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('ticket.validation.status_required'),
            'status.integer' => __('ticket.validation.status_integer'),
            'status.in' => __('ticket.validation.status_invalid'),
        ];
    }
}

