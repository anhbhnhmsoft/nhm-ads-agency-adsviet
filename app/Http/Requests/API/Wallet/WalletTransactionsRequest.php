<?php

namespace App\Http\Requests\API\Wallet;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeValues = array_column(WalletTransactionType::cases(), 'value');
        $statusValues = array_column(WalletTransactionStatus::cases(), 'value');

        return [
            'id' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'integer', Rule::in($typeValues)],
            'status' => ['nullable', 'integer', Rule::in($statusValues)],
            'network' => ['nullable', 'string', 'in:BEP20,TRC20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}



