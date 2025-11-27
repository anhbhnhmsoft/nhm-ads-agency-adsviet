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
            'telegram_hash_invalid' => 'Thông tin telegram không hợp lệ, vui lòng kiểm tra lại.',
            'choose_social_first' => 'Vui lòng chọn phương thức đăng nhập trước đó.',
            'device_id_required' => 'Vui lòng nhập ID thiết bị.',
            'device_name_string' => 'Tên thiết bị phải là một chuỗi ký tự.',
            'device_name_max' => 'Tên thiết bị không quá :max ký tự.',
            'user_disabled' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ với quản trị viên.',
            'email_not_verified' => 'Email của bạn chưa được xác thực. Vui lòng xác thực email trước khi đăng nhập.',
            'user_not_allowed' => 'Tài khoản của bạn không có quyền đăng nhập ở hệ thống này.',
        ],
    ],
    'register' => [
        'success' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực mã OTP.',
        'email_otp_sent' => 'Đã gửi mã xác minh tới :email.',
        'email_otp_failed' => 'Không thể gửi mã xác minh. Vui lòng thử lại sau.',
        'email_otp_invalid' => 'Mã xác minh không hợp lệ.',
        'email_otp_expired' => 'Mã xác minh đã hết hạn.',
        'email_otp_mismatch' => 'Email không khớp với yêu cầu xác minh.',
        'validation' => [
            'role_required' => 'Vui lòng chọn vai trò.',
            'role_in' => 'Vai trò không hợp lệ.',
            'refer_code_required' => 'Vui lòng nhập mã giới thiệu.',
            'refer_code_string' => 'Mã giới thiệu phải là một chuỗi ký tự.',
            'refer_code_invalid' => 'Mã giới thiệu không hợp lệ.',
            'token_invalid' => 'Token đăng ký không hợp lệ.',
        ],
    ],
    'verify_register' => [
        'success' => 'Xác thực đăng ký thành công.',
        'validation' => [
            'otp.required' => 'Vui lòng nhập OTP.',
            'otp.string' => 'OTP phải là một chuỗi ký tự.',
            'otp.max' => 'OTP không quá :max ký tự.',
        ],
    ],
    'forgot_password' => [
        'success' => 'Mật khẩu mới đã được gửi đến Telegram của bạn.',
        'otp' => "OTP Quên mật khẩu \n\nChào bạn 👋 \nMã OTP đổi mật khẩu là: :otp \n\nMã OTP này sẽ hết hạn sau :expire_time phút. \n\nNếu không phải bạn, vui lòng bỏ qua tin nhắn này.",
        'validation' => [
            'user_exists' => 'Tài khoản không tồn tại.',
            'social_or_email_verify' => 'Tài khoản này chưa được xác thực, vui lòng xác thực tài khoản trước khi quên mật khẩu.',
        ],
        'error' => [
            'error_send_otp' => 'Lỗi khi gửi OTP. Vui lòng thử lại sau.',
        ]
    ],
    'verify_forgot_password' => [
        'success' => 'Mật khẩu đã được thay đổi thành công.',
        'validation' => [
            'otp_invalid' => 'OTP không hợp lệ.',
        ],
    ],

];
