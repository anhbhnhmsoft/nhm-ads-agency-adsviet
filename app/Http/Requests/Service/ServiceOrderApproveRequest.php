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
            'meta_email' => ['required', 'email', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'bm_id' => ['required', 'string', 'max:255'],
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


