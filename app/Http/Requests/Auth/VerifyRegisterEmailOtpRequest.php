<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRegisterEmailOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('common_validation.email.required'),
            'email.email' => __('common_validation.email.email'),
            'email.max' => __('common_validation.email.max'),
            'otp.required' => __('auth.verify_register.validation.otp.required'),
            'otp.digits' => __('auth.verify_register.validation.otp.max', ['max' => 6]),
        ];
    }
}

