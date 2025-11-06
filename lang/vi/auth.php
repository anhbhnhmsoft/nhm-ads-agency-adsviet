<?php

return [
    'login' => [
        'success' => 'Đăng nhập thành công.',
        'need_register' => 'Bạn cần đăng ký trước khi đăng nhập.',
        'validation' => [
            'rate_limit' => 'Tên đăng nhập hoặc mật khẩu không đúng. Vui lòng thử lại sau :seconds giây',
            'invalid_credentials' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ.',
            'device_required' => 'Vui lòng chọn thiết bị.',
            'device_in' => 'Thiết bị không hợp lệ.',
            'telegram_hash_invalid' => 'Telegram không hợp lệ.',
            'choose_social_first' => 'Vui lòng chọn phương thức đăng nhập trước đó.',
            'device_id_required' => 'Vui lòng nhập ID thiết bị.',
            'device_name_string' => 'Tên thiết bị phải là một chuỗi ký tự.',
            'device_name_max' => 'Tên thiết bị không quá :max ký tự.',
            'user_disabled' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ với quản trị viên.',
        ],
    ],
    'register' => [
        'validation' => [
            'role_required' => 'Vui lòng chọn vai trò.',
            'role_in' => 'Vai trò không hợp lệ.',
            'refer_code_required' => 'Vui lòng nhập mã giới thiệu.',
            'refer_code_string' => 'Mã giới thiệu phải là một chuỗi ký tự.',
            'refer_code_invalid' => 'Mã giới thiệu không hợp lệ.',
        ],
    ],

];
