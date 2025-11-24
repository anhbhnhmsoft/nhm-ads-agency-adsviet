<?php

namespace App\Http\Requests\PlatformSetting;

use Illuminate\Foundation\Http\FormRequest;

class PlatformSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'integer'],
            'config' => ['nullable', 'array'],
            'disabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.integer' => __('common_validation.platform_integer'),
            'config.array' => __('common_validation.config_array'),
            'disabled.boolean' => __('common_validation.disabled_boolean'),
        ];
    }
}

