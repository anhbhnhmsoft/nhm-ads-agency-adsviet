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
        'regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số.',
        'string' => 'Mật khẩu không hợp lệ.',
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
    ],
    'current_password' => [
        'required' => 'Mật khẩu hiện tại là bắt buộc.',
        'string' => 'Mật khẩu hiện tại không hợp lệ.',
    ],
    'new_password' => [
        'required' => 'Mật khẩu mới là bắt buộc.',
        'string' => 'Mật khẩu mới không hợp lệ.',
        'min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
        'confirmed' => 'Xác nhận mật khẩu mới không khớp.',
    ],
    'phone' => [
        'string' => 'Số điện thoại không hợp lệ.',
        'max' => 'Số điện thoại không được vượt quá :max ký tự.',
        'unique' => 'Số điện thoại đã tồn tại, vui lòng chọn số khác.',
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
    'amount' => [
        'required' => 'Số tiền là bắt buộc.',
        'numeric' => 'Số tiền phải là một số.',
        'gt' => 'Số tiền phải lớn hơn 0.',
        'min' => 'Số tiền tối thiểu là :min.',
    ],
    'network' => [
        'required' => 'Mạng là bắt buộc.',
        'string' => 'Mạng phải là một chuỗi ký tự.',
        'in' => 'Mạng không hợp lệ. Chỉ chấp nhận BEP20 hoặc TRC20.',
    ],
];
