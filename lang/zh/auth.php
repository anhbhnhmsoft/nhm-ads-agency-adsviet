<?php

return [
    'login' => [
        'success' => '登录成功。',
        'need_register' => '请先注册再登录。',
        'validation' => [
            'rate_limit' => '用户名或密码无效。请在:seconds秒后重试。',
            'invalid_credentials' => '用户名或密码无效。',
            'role.required' => '请选择一个角色。',
            'role.in' => '所选角色无效。',
            'device_required' => '请选择一个设备。',
            'device_in' => '所选设备无效。',
            'telegram_hash_invalid' => '电报信息无效。请再次检查。',
            'choose_social_first' => '请选择以前的登录方式。',
            'device_id_required' => '请输入设备ID。',
            'device_name_string' => '设备名称必须是字符串。',
            'device_name_max' => '设备名称不得大于:max个字符。',
            'user_disabled' => '您的帐户已被锁定。请联系管理员。',
            'email_not_verified' => '您的电子邮件尚未经过验证。请在登录前验证。',
            'user_not_allowed' => '您的帐户无法访问该系统。',
        ],
    ],
    'register' => [
        'success' => '注册成功。请检查您的电子邮件以获取 OTP 代码。',
        'email_otp_sent' => '验证码发送至:email。',
        'email_otp_failed' => '无法发送验证码。请稍后重试。',
        'email_otp_invalid' => '验证码无效。',
        'email_otp_expired' => '验证码已过期。',
        'email_otp_mismatch' => '电子邮件与验证请求不匹配。',
        'validation' => [
            'role_required' => '请选择一个角色。',
            'role_in' => '所选角色无效。',
            'refer_code_required' => '请输入推荐代码。',
            'refer_code_string' => '推荐码必须是字符串。',
            'refer_code_invalid' => '推荐代码无效。',
            'token_invalid' => '注册令牌无效。',
        ],
    ],
    'verify_register' => [
        'success' => '注册验证成功。',
        'validation' => [
            'otp.required' => '请输入一次性密码。',
            'otp.string' => 'OTP 必须是字符串。',
            'otp.max' => 'OTP 不得大于:max个字符。',
        ],
    ],
    'forgot_password' => [
        'success' => '新密码已发送至您的 Telegram。',
        'otp' => "忘记密码 OTP\n\n您好 👋\n您的密码重置 OTP 是：:otp\n\n此 OTP 将在:expire_time分钟后过期。\n\n如果不是您本人，请忽略此消息。",
        'validation' => [
            'user_exists' => '帐户不存在。',
            'social_or_email_verify' => '该帐户尚未经过验证。请在请求重置密码之前进行验证。',
        ],
        'error' => [
            'error_send_otp' => '无法发送 OTP。请稍后重试。',
        ],
    ],
    'verify_forgot_password' => [
        'success' => '密码更新成功。',
        'validation' => [
            'otp_invalid' => 'OTP 无效。',
        ],
    ],
];

