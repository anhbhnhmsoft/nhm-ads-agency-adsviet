<?php

namespace App\Http\Requests\Ticket;

use App\Common\Constants\Ticket\TicketPriority;
use App\Common\Constants\Ticket\TicketMetadataType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'metadata' => ['nullable', 'array'],
            'metadata.type' => ['required_with:metadata', 'string', Rule::in(TicketMetadataType::values())],
            'metadata.amount' => ['required_if:metadata.type,wallet_withdraw_app,metadata.type,wallet_deposit_app', 'numeric', 'gt:0'],
            'metadata.withdraw_type' => ['required_if:metadata.type,wallet_withdraw_app', Rule::in(['bank', 'usdt'])],
            'metadata.withdraw_info' => ['nullable', 'array'],
            'metadata.withdraw_info.bank_name' => ['required_if:metadata.withdraw_type,bank', 'string', 'max:255'],
            'metadata.withdraw_info.account_holder' => ['required_if:metadata.withdraw_type,bank', 'string', 'max:255'],
            'metadata.withdraw_info.account_number' => ['required_if:metadata.withdraw_type,bank', 'string', 'max:255'],
            'metadata.withdraw_info.crypto_address' => ['required_if:metadata.withdraw_type,usdt', 'string', 'max:255'],
            'metadata.withdraw_info.network' => ['required_if:metadata.withdraw_type,usdt', Rule::in(['TRC20', 'BEP20'])],
            'metadata.network' => ['required_if:metadata.type,wallet_deposit_app', Rule::in(['TRC20', 'BEP20'])],
            'wallet_password' => ['required_if:metadata.type,wallet_withdraw_app', 'string', 'max:255'],
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

