<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => __('common_validation.password.required'),
            'password.string' => __('common_validation.password.string'),
            'password.min' => __('common_validation.password.min', ['min' => 6]),
        ];
    }
}
