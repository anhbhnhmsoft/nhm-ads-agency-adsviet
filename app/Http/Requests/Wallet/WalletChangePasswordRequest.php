<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['nullable', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.string' => __('common_validation.password.string'),
            'new_password.required' => __('common_validation.password.required'),
            'new_password.string' => __('common_validation.password.string'),
            'new_password.min' => __('common_validation.password.min', ['min' => 6]),
        ];
    }
}
