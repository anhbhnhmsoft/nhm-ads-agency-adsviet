<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceOrderUpdateConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta_email' => ['nullable', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'in:prepay,postpay'],
        ];
    }

    public function messages(): array
    {
        return [
            'meta_email.email' => __('services.validation.meta_email_email'),
            'meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'display_name.string' => __('services.validation.display_name_string'),
            'display_name.max' => __('services.validation.display_name_max', ['max' => 255]),
            'bm_id.string' => __('services.validation.bm_id_string'),
            'bm_id.max' => __('services.validation.bm_id_max', ['max' => 255]),
        ];
    }
}

