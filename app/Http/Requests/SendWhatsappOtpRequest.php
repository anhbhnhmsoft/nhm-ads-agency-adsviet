<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsappOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:11'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('common_validation.phone.required'),
            'phone.string'     => __('common_validation.phone.string'),
            'phone.max'     => __('common_validation.phone.max'),
        ];
    }
}
