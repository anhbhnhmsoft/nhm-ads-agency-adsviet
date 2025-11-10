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
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        'regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số.',
    ],
    'phone' => [
        'string' => 'Số điện thoại không hợp lệ.',
        'max' => 'Số điện thoại không được vượt quá :max ký tự.',
    ],
    'email' => [
        'required' => 'Email là bắt buộc.',
        'string' => 'Email phải là một chuỗi ký tự.',
        'email' => 'Email không hợp lệ.',
        'max' => 'Email không được vượt quá :max ký tự.',
        'unique' => 'Email đã tồn tại, vui lòng chọn email khác.',
    ],
    'role' => [
        'required' => 'Vui lòng chọn vai trò.',
        'invalid' => 'Vai trò không hợp lệ.',
    ],
    'refer_code' => [
        'required' => 'Mã giới thiệu là bắt buộc.',
        'invalid' => 'Mã giới thiệu không hợp lệ.',
    ],
    'disabled' => [
        'required' => 'Trạng thái là bắt buộc.',
        'boolean' => 'Trạng thái không hợp lệ.',
    ],
    'otp_invalid' => 'Mã OTP không hợp lệ.',
    'user_id' => [
        'required' => 'Người dùng là bắt buộc.',
        'string' => 'Người dùng phải là một chuỗi ký tự.',
        'exists' => 'Người dùng không tồn tại.',
    ],
    'token_invalid' => 'Token không hợp lệ.',
];
