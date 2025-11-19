<?php

namespace App\Http\Requests\API\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WalletCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}

