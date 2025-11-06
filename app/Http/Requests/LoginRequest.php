<?php

namespace App\Http\Requests;

use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => [new PasswordRule],
            'role' => ['required', 'in:admin,user'],
        ];
    }

    public function messages()
    {
        return [
            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
            'role.required' => __('auth.login.role.required'),
            'role.in' => __('auth.login.role.in'),
        ];
    }
}
