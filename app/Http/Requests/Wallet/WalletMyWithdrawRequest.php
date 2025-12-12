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
        $withdrawType = $this->input('withdraw_type', 'bank');
        
        $rules = [
            'amount' => ['required', 'numeric', 'gt:0'],
            'password' => ['nullable', 'string'],
            'withdraw_type' => ['nullable', 'string', 'in:bank,usdt'],
        ];

        if ($withdrawType === 'usdt') {
            $rules['crypto_address'] = ['required', 'string', 'max:255'];
            $rules['network'] = ['required', 'string', 'in:TRC20,BEP20'];
        } else {
            $rules['bank_name'] = ['required', 'string', 'max:255'];
            $rules['account_holder'] = ['required', 'string', 'max:255'];
            $rules['account_number'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.required' => __('common_validation.amount.required'),
            'amount.numeric' => __('common_validation.amount.numeric'),
            'amount.gt' => __('common_validation.amount.gt'),
            'password.string' => __('common_validation.password.string'),
            'withdraw_type.in' => __('wallet.validation.withdraw_type_invalid'),
            'bank_name.required' => __('wallet.validation.bank_name_required'),
            'bank_name.max' => __('wallet.validation.bank_name_max', ['max' => 255]),
            'account_holder.required' => __('wallet.validation.account_holder_required'),
            'account_holder.max' => __('wallet.validation.account_holder_max', ['max' => 255]),
            'account_number.required' => __('wallet.validation.account_number_required'),
            'account_number.max' => __('wallet.validation.account_number_max', ['max' => 255]),
            'crypto_address.required' => __('wallet.validation.crypto_address_required'),
            'crypto_address.max' => __('wallet.validation.crypto_address_max', ['max' => 255]),
            'network.required' => __('wallet.validation.network_required'),
            'network.in' => __('wallet.validation.network_invalid'),
        ];
    }
}
