<?php

namespace App\Http\Requests\API\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Common\Constants\Platform\PlatformType;

class WalletCampaignBudgetUpdateRequest extends FormRequest
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
            'campaign_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
        ];
    }
}


