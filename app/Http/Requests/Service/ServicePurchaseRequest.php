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
            'budget' => ['required', 'numeric', 'min:50'],
            'meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'in:prepay,postpay'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => __('services.validation.package_required'),
            'top_up_amount.numeric' => __('services.validation.top_up_numeric'),
            'top_up_amount.min' => __('services.validation.top_up_min'),
            'budget.required' => __('services.validation.budget_required'),
            'budget.numeric' => __('services.validation.budget_numeric'),
            'budget.min' => __('services.validation.budget_min', ['min' => 50]),
            'meta_email.email' => __('services.validation.meta_email_email'),
            'meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'display_name.max' => __('services.validation.display_name_max', ['max' => 255]),
        ];
    }
}


