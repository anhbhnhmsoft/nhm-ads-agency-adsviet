<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use App\Common\Constants\Platform\PlatformType;

class CreateAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'integer', 'in:' . PlatformType::GOOGLE->value . ',' . PlatformType::META->value],
            'account_id' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => __('ticket.appeal.platform_required'),
            'platform.in' => __('ticket.appeal.platform_invalid'),
            'account_id.required' => __('ticket.appeal.account_id_required'),
            'account_id.string' => __('ticket.appeal.account_id_invalid'),
            'notes.required' => __('ticket.appeal.notes_required'),
            'notes.max' => __('ticket.appeal.notes_max'),
        ];
    }
}

