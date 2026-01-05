<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'string'],
            'budget' => ['required', 'numeric', 'min:50'],
            'meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bm_id' => ['nullable', 'string', 'max:255'],
            'info_fanpage' => ['nullable', 'string', 'max:255'],
            'info_website' => ['nullable', 'string', 'max:255'],
            'asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
            'timezone_bm' => ['nullable', 'string'],
            'accounts' => ['nullable', 'array', 'max:3'],
            'accounts.*.meta_email' => ['nullable', 'string', 'email', 'max:255'],
            'accounts.*.display_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.bm_ids' => ['nullable', 'array', 'max:3'],
            'accounts.*.bm_ids.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.fanpages' => ['nullable', 'array', 'max:3'],
            'accounts.*.fanpages.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.websites' => ['nullable', 'array', 'max:3'],
            'accounts.*.websites.*' => ['nullable', 'string', 'max:255'],
            'accounts.*.timezone_bm' => ['nullable', 'string'],
            'accounts.*.asset_access' => ['nullable', 'string', 'in:full_asset,basic_asset'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => __('services.validation.package_required'),
            'budget.required' => __('services.validation.budget_required'),
            'budget.numeric' => __('services.validation.budget_numeric'),
            'budget.min' => __('services.validation.budget_min', ['min' => 50]),
            'meta_email.email' => __('services.validation.meta_email_email'),
            'meta_email.max' => __('services.validation.meta_email_max', ['max' => 255]),
            'display_name.max' => __('services.validation.display_name_max', ['max' => 255]),
            'notes.max' => __('common_validation.max', ['max' => 2000]),
        ];
    }
}
