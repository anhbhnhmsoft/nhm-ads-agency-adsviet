<?php

namespace App\Http\Requests\API\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'wallet_password' => ['nullable', 'string'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
        ];
    }
}

