<?php 

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'username' => ['required','string','max:255','unique:users,username'],
            'password' => ['required','string'],
            'phone' => ['nullable','string','max:30'],
            'role' => ['required','integer'],
            'disabled' => ['required','boolean'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('common_validation.name.required'),
            'name.string' => __('common_validation.name.string'),
            'name.max' => __('common_validation.name.max', ['max' => 255]),

            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
            'username.unique' => __('common_validation.username.unique'),

            'password.required' => __('common_validation.password.required'),
            'password.string' => __('common_validation.password.string'),

            'phone.string' => __('common_validation.phone.string'),
            'phone.max' => __('common_validation.phone.max', ['max' => 30]),

            'role.required' => __('common_validation.role.required'),
            'role.integer' => __('common_validation.role.integer'),

            'disabled.required' => __('common_validation.disabled.required'),
            'disabled.boolean' => __('common_validation.disabled.boolean'),
        ];
    }
}