<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceOrderApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_type' => ['nullable', 'string', 'in:prepay,postpay'],            
            'meta_email' => ['nullable', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'child_bm_id' => ['nullable', 'string', 'max:255'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
            'timezone_bm' => ['nullable', 'string'],
            
            'accounts' => ['nullable', 'array', 'max:3'],
            'accounts.*.meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'accounts.*.display_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.bm_ids' => ['nullable', 'array', 'max:3'],
            'accounts.*.bm_ids.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.fanpages' => ['nullable', 'array', 'max:3'],
            'accounts.*.fanpages.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.websites' => ['nullable', 'array', 'max:3'],
            'accounts.*.websites.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.timezone_bm' => ['nullable', 'string'],
            'accounts.*.asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
        ];
    }

    public function messages(): array
    {
        return [
            'meta_email.required' => __('services.validation.meta_email_required'),
            'meta_email.email' => __('services.validation.meta_email_email'),
            'meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'display_name.required' => __('services.validation.display_name_required'),
            'display_name.max' => __('services.validation.display_name_max', ['max' => 255]),
            'bm_id.required' => __('services.validation.bm_id_required'),
            'bm_id.max' => __('services.validation.bm_id_max', ['max' => 255]),
        ];
    }
}


