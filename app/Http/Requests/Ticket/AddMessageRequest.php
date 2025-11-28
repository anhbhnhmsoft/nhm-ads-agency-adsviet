<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AddMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => __('ticket.validation.message_required'),
            'message.string' => __('ticket.validation.message_string'),
            'message.max' => __('ticket.validation.message_max', ['max' => 5000]),
        ];
    }
}

