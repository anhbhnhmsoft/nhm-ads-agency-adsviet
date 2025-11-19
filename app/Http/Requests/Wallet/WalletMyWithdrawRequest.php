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
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => __('common_validation.amount.required'),
            'amount.numeric' => __('common_validation.amount.numeric'),
            'amount.gt' => __('common_validation.amount.gt'),
            'password.string' => __('common_validation.password.string'),
            'bank_name.required' => __('wallet.validation.bank_name_required'),
            'bank_name.max' => __('wallet.validation.bank_name_max', ['max' => 255]),
            'account_holder.required' => __('wallet.validation.account_holder_required'),
            'account_holder.max' => __('wallet.validation.account_holder_max', ['max' => 255]),
            'account_number.required' => __('wallet.validation.account_number_required'),
            'account_number.max' => __('wallet.validation.account_number_max', ['max' => 255]),
        ];
    }
}
