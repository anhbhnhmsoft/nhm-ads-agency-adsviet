<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'open_fee' => ['required', 'numeric', 'min:0'],
            'supplier_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'monthly_spending_fee_structure' => ['nullable', 'array'],
            'monthly_spending_fee_structure.*.range' => ['required_with:monthly_spending_fee_structure', 'string', 'max:255'],
            'monthly_spending_fee_structure.*.fee_percent' => ['required_with:monthly_spending_fee_structure', 'string', 'max:50'],
            'disabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên nhà cung cấp là bắt buộc',
            'open_fee.required' => 'Chi phí mở tài khoản là bắt buộc',
            'open_fee.numeric' => 'Chi phí mở tài khoản phải là số',
            'open_fee.min' => 'Chi phí mở tài khoản không được nhỏ hơn 0',
            'supplier_fee_percent.numeric' => 'Chi phí nhà cung cấp (%) phải là số',
            'supplier_fee_percent.min' => 'Chi phí nhà cung cấp (%) không được nhỏ hơn 0',
            'supplier_fee_percent.max' => 'Chi phí nhà cung cấp (%) không được lớn hơn 100',
        ];
    }
}

