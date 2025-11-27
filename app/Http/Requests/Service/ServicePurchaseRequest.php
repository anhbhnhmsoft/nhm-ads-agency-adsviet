<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServicePurchaseRequest extends FormRequest
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
            'budget' => ['required', 'numeric', 'min:0', 'max:50'],
            'meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => __('Vui lòng chọn gói dịch vụ'),
            'top_up_amount.numeric' => __('Số tiền top-up không hợp lệ'),
            'top_up_amount.min' => __('Số tiền top-up phải lớn hơn hoặc bằng 0'),
            'budget.required' => __('Ngân sách là bắt buộc'),
            'budget.numeric' => __('Ngân sách phải là số'),
            'budget.min' => __('Ngân sách phải lớn hơn hoặc bằng 0'),
            'budget.max' => __('Ngân sách không được vượt quá 50 USD'),
        ];
    }
}


