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
        ];
    }
}

