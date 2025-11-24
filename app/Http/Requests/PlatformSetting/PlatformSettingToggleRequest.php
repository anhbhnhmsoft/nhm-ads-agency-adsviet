<?php

namespace App\Http\Requests\PlatformSetting;

use Illuminate\Foundation\Http\FormRequest;

class PlatformSettingToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'disabled.required' => __('common_validation.disabled_required'),
            'disabled.boolean' => __('common_validation.disabled_boolean'),
        ];
    }
}

