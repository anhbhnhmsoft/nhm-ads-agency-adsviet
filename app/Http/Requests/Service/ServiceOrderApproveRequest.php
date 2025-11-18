<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceOrderApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta_email' => ['required', 'email', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'bm_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'meta_email.required' => __('Vui lòng nhập Email Meta'),
            'meta_email.email' => __('Email Meta không hợp lệ'),
            'display_name.required' => __('Vui lòng nhập tên hiển thị'),
            'bm_id.required' => __('Vui lòng nhập Business Manager ID'),
        ];
    }
}


