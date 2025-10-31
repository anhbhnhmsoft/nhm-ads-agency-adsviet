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
    ],
    'password' => [
        'required' => 'Mật khẩu là bắt buộc.',
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        'regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số.',
    ],
    'otp' => [
        'required' => 'Mã OTP là bắt buộc',
        'digits' => 'Mã OTP phải gồm :digits chữ số.'
    ],
    'phone' => [
        'required' => 'Số điện thoại là bắt buộc.',
        'string' => 'Số điện thoại phải là một chuỗi ký tự.',
        'max' => 'Số điện thoại không được quá :max số',
    ]
];
