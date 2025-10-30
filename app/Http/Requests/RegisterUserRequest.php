<?php

namespace App\Http\Requests;

use App\Common\Constants\User\UserRole;
use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255','unique:users,username'],
            'password' => [new PasswordRule],
            'role' => ['required', Rule::in([UserRole::CUSTOMER->value, UserRole::AGENCY->value])],
            'refer_code' => 'required|string|exists:users,referral_code',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('common_validation.name.required'),
            'name.string' => __('common_validation.name.string'),
            'name.max' => __('common_validation.name.max', ['max' => 255]),
            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
            'role.required' => __('auth.register.validation.role.required'),
            'role.in' => __('auth.register.validation.role.in'),
            'refer_code.required' => __('auth.register.validation.refer_code_required'),
            'refer_code.string' => __('auth.register.validation.refer_code_string'),
            'refer_code.exists' => __('auth.register.validation.refer_code_invalid'),
        ];
    }
}
