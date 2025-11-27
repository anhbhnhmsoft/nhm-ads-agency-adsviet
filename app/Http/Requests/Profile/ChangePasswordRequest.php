<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => __('common_validation.current_password.required'),
            'current_password.string' => __('common_validation.current_password.string'),
            'new_password.required' => __('common_validation.new_password.required'),
            'new_password.string' => __('common_validation.new_password.string'),
            'new_password.min' => __('common_validation.new_password.min', ['min' => 6]),
            'new_password.confirmed' => __('common_validation.new_password.confirmed'),
        ];
    }
}

