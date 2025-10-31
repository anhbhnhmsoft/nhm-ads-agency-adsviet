<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWhatsappOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => __('common_validation.otp.required'),
            'otp.digits'     => __('common_validation.otp.digits'),
        ];
    }
}
