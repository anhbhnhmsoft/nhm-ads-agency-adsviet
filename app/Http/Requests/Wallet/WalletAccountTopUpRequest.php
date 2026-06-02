<?php

namespace App\Http\Requests\Wallet;

use App\Common\Constants\Platform\PlatformType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletAccountTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'wallet_password' => ['nullable', 'string', 'min:6'],
            'platform_type' => ['required', 'integer', Rule::in(PlatformType::getValues())],
            'service_user_id' => ['nullable', 'string', 'exists:service_users,id'],
            'account_id' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
        ];
    }
}
