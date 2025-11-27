<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (string) ($this->user()?->id);

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:12',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('common_validation.name.required'),
            'name.string' => __('common_validation.name.string'),
            'name.max' => __('common_validation.name.max'),

            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max'),
            'username.unique' => __('common_validation.username.unique'),

            'email.email' => __('common_validation.email.email'),
            'email.max' => __('common_validation.email.max'),
            'email.unique' => __('common_validation.email.unique'),

            'phone.string' => __('common_validation.phone.string'),
            'phone.max' => __('common_validation.phone.max'),
            'phone.unique' => __('common_validation.phone.unique'),
        ];
    }
}

