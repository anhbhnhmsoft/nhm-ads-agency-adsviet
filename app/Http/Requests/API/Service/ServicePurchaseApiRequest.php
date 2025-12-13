<?php

namespace App\Http\Requests\API\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServicePurchaseApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'string'],
            'top_up_amount' => ['nullable', 'numeric', 'min:0'],
            'budget' => ['required', 'numeric', 'min:50'],
            'meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'in:prepay,postpay'],
            'asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
        ];
    }
}

