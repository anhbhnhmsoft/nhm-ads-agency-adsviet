<?php

namespace App\Http\Requests\API\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'network' => ['required', 'string', 'in:BEP20,TRC20'],
        ];
    }
}


