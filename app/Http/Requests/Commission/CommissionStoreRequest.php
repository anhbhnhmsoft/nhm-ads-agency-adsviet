<?php

namespace App\Http\Requests\Commission;

use App\Models\EmployeeCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_package_id' => ['required', 'string', 'exists:service_packages,id'],
            'type' => ['required', 'string', Rule::in([
                EmployeeCommission::TYPE_SERVICE,
                EmployeeCommission::TYPE_SPENDING,
                EmployeeCommission::TYPE_ACCOUNT,
            ])],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'gt:min_amount'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

