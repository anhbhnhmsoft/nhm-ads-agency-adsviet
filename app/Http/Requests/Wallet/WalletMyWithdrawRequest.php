<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletMyWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'password' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => __('common_validation.amount.required'),
            'amount.numeric' => __('common_validation.amount.numeric'),
            'amount.gt' => __('common_validation.amount.gt'),
            'password.string' => __('common_validation.password.string'),
        ];
    }
}
