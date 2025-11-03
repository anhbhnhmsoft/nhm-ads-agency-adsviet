<?php


return [
    'name' => [
        'required' => 'Họ tên là bắt buộc.',
        'string' => 'Họ tên phải là một chuỗi ký tự.',
        'max' => 'Họ tên không được vượt quá :max ký tự.',
    ],
    'username' => [
        'required' => 'Tên đăng nhập là bắt buộc.',
        'string' => 'Tên đăng nhập phải là một chuỗi ký tự.',
        'max' => 'Tên đăng nhập không được vượt quá :max ký tự.',
        'unique' => 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác.',
    ],
    'password' => [
        'required' => 'Mật khẩu là bắt buộc.',
        'string' => 'Mật khẩu không hợp lệ.',
    ],
    'phone' => [
        'string' => 'Số điện thoại không hợp lệ.',
        'max' => 'Số điện thoại không được vượt quá :max ký tự.',
    ],
    'role' => [
        'required' => 'Vui lòng chọn vai trò.',
        'integer' => 'Vai trò không hợp lệ.',
    ],
    'disabled' => [
        'required' => 'Trạng thái là bắt buộc.',
        'boolean' => 'Trạng thái không hợp lệ.',
    ],
];
