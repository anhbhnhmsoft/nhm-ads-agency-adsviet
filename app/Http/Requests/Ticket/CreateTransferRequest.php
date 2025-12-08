<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'integer', 'in:1,2'], // 1 = META, 2 = GOOGLE
            'from_account_id' => ['required', 'string', 'max:255'],
            'to_account_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => __('ticket.transfer.platform_required'),
            'platform.in' => __('ticket.transfer.platform_invalid'),
            'from_account_id.required' => __('ticket.transfer.from_account_required'),
            'to_account_id.required' => __('ticket.transfer.to_account_required'),
            'amount.required' => __('ticket.transfer.amount_required'),
            'amount.numeric' => __('ticket.transfer.amount_numeric'),
            'amount.min' => __('ticket.transfer.amount_min'),
            'notes.max' => __('ticket.transfer.notes_max'),
        ];
    }
}

