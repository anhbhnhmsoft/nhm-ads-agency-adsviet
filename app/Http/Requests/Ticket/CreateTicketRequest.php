<?php

namespace App\Http\Requests\Ticket;

use App\Common\Constants\Ticket\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'integer', 'in:' . implode(',', array_column(TicketPriority::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => __('ticket.validation.subject_required'),
            'subject.string' => __('ticket.validation.subject_string'),
            'subject.max' => __('ticket.validation.subject_max', ['max' => 255]),
            'description.required' => __('ticket.validation.description_required'),
            'description.string' => __('ticket.validation.description_string'),
            'description.max' => __('ticket.validation.description_max', ['max' => 5000]),
            'priority.integer' => __('ticket.validation.priority_integer'),
            'priority.in' => __('ticket.validation.priority_invalid'),
        ];
    }
}

