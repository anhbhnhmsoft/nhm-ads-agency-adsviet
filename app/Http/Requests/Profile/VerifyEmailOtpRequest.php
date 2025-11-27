<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => __('common_validation.otp_invalid'),
            'otp.string' => __('common_validation.otp_invalid'),
            'otp.size' => __('common_validation.otp_invalid'),
        ];
    }
}

