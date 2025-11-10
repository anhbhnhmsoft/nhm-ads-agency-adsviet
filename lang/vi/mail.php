<?php


return [
    'verify_email_register' => [
        'subject' => 'Xác nhận đăng ký tài khoản',
        'greeting' => 'Xin chào :username',
        'thank_for_register' => 'Cảm ơn bạn đã đăng ký tài khoản. Đây là mã xác nhận của bạn: :otp',
        'expire' => 'Mã OTP này sẽ hết hạn sau :expire_time phút.',
        'footer' => 'Nếu bạn không yêu cầu, vui lòng bỏ qua email này.',
        'thanks' => 'Trân trọng,',
    ],
    'verify_email_forgot_password' => [
        'subject' => 'Xác nhận đặt lại mật khẩu',
        'greeting' => 'Xin chào :username',
        'otp' => 'Đây là mã xác nhận đặt lại mật khẩu của bạn: :otp',
        'expire' => 'Mã OTP này sẽ hết hạn sau :expire_time phút.',
        'footer' => 'Nếu bạn không yêu cầu, vui lòng bỏ qua email này.',
        'thanks' => 'Trân trọng,',
    ],
];
