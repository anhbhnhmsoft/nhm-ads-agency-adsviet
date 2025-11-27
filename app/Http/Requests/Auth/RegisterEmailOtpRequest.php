<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterEmailOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('common_validation.email.required'),
            'email.email' => __('common_validation.email.email'),
            'email.max' => __('common_validation.email.max'),
            'email.unique' => __('common_validation.email.unique'),
        ];
    }
}

