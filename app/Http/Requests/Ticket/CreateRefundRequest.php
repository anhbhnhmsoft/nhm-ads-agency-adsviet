<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use App\Common\Constants\Platform\PlatformType;

class CreateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'integer', 'in:' . PlatformType::GOOGLE->value . ',' . PlatformType::META->value],
            'account_ids' => ['required', 'array', 'min:1'],
            'account_ids.*' => ['required', 'string', 'max:255'],
            'liquidation_type' => ['required', 'string', 'in:withdraw_to_wallet'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => __('ticket.refund.platform_required'),
            'platform.in' => __('ticket.refund.platform_invalid'),
            'account_ids.required' => __('ticket.refund.account_ids_required'),
            'account_ids.array' => __('ticket.refund.account_ids_invalid'),
            'account_ids.min' => __('ticket.refund.account_ids_min'),
            'liquidation_type.required' => __('ticket.refund.liquidation_type_required'),
            'liquidation_type.in' => __('ticket.refund.liquidation_type_invalid'),
            'notes.required' => __('ticket.refund.notes_required'),
            'notes.max' => __('ticket.refund.notes_max'),
        ];
    }
}

