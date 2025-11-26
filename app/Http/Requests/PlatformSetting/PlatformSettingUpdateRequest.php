<?php

namespace App\Http\Requests\PlatformSetting;

use App\Common\Constants\Platform\PlatformSettingFields;
use Illuminate\Foundation\Http\FormRequest;

class PlatformSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'platform' => ['nullable', 'integer'],
            'config' => ['nullable', 'array'],
            'disabled' => ['nullable', 'boolean'],
        ];

        $platform = $this->input('platform');
        $config = $this->input('config');
        
        if ($config !== null && is_array($config) && $platform) {
            $fields = PlatformSettingFields::getFieldsByPlatform((int) $platform);
            foreach ($fields as $field) {
                $key = "config.{$field['key']}";
                if ($field['required']) {
                    if (array_key_exists($field['key'], $config)) {
                        $rules[$key] = ['required'];
                    } else {
                        $rules[$key] = ['nullable'];
                    }
                } else {
                    $rules[$key] = ['nullable'];
                }

                if ($field['type'] === 'textarea') {
                    $rules[$key][] = 'array';
                } else {
                    $rules[$key][] = 'string';
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'platform.integer' => __('common_validation.platform_integer'),
            'config.array' => __('common_validation.config_array'),
            'disabled.boolean' => __('common_validation.disabled_boolean'),
        ];

        $platform = $this->input('platform');
        $config = $this->input('config');
        
        if ($config !== null && $platform) {
            $fields = PlatformSettingFields::getFieldsByPlatform((int) $platform);
            foreach ($fields as $field) {
                $key = "config.{$field['key']}";
                if ($field['required']) {
                    $messages["{$key}.required"] = __('platform.validation.field_required', [
                        'field' => $field['label']
                    ]);
                }
                if ($field['type'] === 'textarea') {
                    $messages["{$key}.array"] = __('platform.validation.field_array', [
                        'field' => $field['label']
                    ]);
                } else {
                    $messages["{$key}.string"] = __('platform.validation.field_string', [
                        'field' => $field['label']
                    ]);
                }
            }
        }

        return $messages;
    }
}

