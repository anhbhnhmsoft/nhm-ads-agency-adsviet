<?php 

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => ['required','string','max:255'],
            'password' => ['nullable','string'],
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

