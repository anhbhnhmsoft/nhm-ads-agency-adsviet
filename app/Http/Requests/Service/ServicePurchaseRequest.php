<?php

namespace App\Http\Requests\Service;

use App\Common\Constants\User\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ServicePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $isStaff = $user && in_array($user->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ], true);

        $rules = [
            'package_id' => ['required', 'string'],
            'top_up_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['nullable', 'string', 'in:prepay,postpay'],
            'meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
            'asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
            'timezone_bm' => ['nullable', 'string'],
            'accounts' => ['nullable', 'array', 'max:20'],
            'accounts.*.meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'accounts.*.display_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.bm_ids' => ['nullable', 'array'],
            'accounts.*.bm_ids.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.fanpages' => ['nullable', 'array'],
            'accounts.*.fanpages.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.websites' => ['nullable', 'array'],
            'accounts.*.websites.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.timezone_bm' => ['nullable', 'string'],
            'accounts.*.asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
        ];

        if ($isStaff) {
            $rules['customer_id'] = ['required', 'string', 'exists:users,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'package_id.required' => __('services.validation.package_required'),
            'top_up_amount.numeric' => __('services.validation.top_up_numeric'),
            'top_up_amount.min' => __('services.validation.top_up_min'),
            'meta_email.email' => __('services.validation.meta_email_email'),
            'meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'accounts.*.meta_email.email' => __('services.validation.meta_email_email'),
            'accounts.*.meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'display_name.max' => __('services.validation.display_name_max', ['max' => 255]),
            'customer_id.required' => __('services.validation.customer_required'),
            'customer_id.exists' => __('services.validation.customer_invalid'),
        ];
    }
}
