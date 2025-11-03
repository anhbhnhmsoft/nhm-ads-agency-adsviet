<?php 

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ToggleDisableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disabled' => ['required','boolean'],
        ];
    }

    public function messages()
    {
        return [
            'disabled.required' => __('common_validation.disabled.required'),
            'disabled.boolean' => __('common_validation.disabled.boolean'),
        ];
    }
}