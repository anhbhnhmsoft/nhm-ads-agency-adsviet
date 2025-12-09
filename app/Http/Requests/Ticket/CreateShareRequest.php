<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use App\Common\Constants\Platform\PlatformType;

class CreateShareRequest extends FormRequest
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
            'bm_bc_mcc_id' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => __('ticket.share.platform_required'),
            'platform.in' => __('ticket.share.platform_invalid'),
            'account_id.required' => __('ticket.share.account_id_required'),
            'account_id.string' => __('ticket.share.account_id_invalid'),
            'bm_bc_mcc_id.required' => __('ticket.share.bm_bc_mcc_id_required'),
            'bm_bc_mcc_id.string' => __('ticket.share.bm_bc_mcc_id_invalid'),
            'notes.required' => __('ticket.share.notes_required'),
            'notes.max' => __('ticket.share.notes_max'),
        ];
    }
}
