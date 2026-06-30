<?php

return [
    'name' => [
        'required' => '需要全名。',
        'string' => '全名必须是字符串。',
        'max' => '全名不得超过:max个字符。',
    ],
    'username' => [
        'required' => '需要用户名。',
        'string' => '用户名必须是字符串。',
        'max' => '用户名不得超过:max个字符。',
        'unique' => '用户名已存在，请选择其他用户名。',
    ],
    'password' => [
        'required' => '需要密码。',
        'regex' => '密码必须至少包含 1 个大写字母、1 个小写字母和 1 个数字。',
        'string' => '密码无效。',
        'min' => '密码必须至少包含:min个字符。',
    ],
    'password_confirmation_mismatch' => '密码确认不匹配。',
    'current_password' => [
        'required' => '需要当前密码。',
        'string' => '当前密码无效。',
    ],
    'new_password' => [
        'required' => '需要新密码。',
        'string' => '新密码无效。',
        'min' => '新密码必须至少包含:min个字符。',
        'confirmed' => '密码确认不匹配。',
    ],
    'phone' => [
        'string' => '电话号码无效。',
        'max' => '电话号码不得超过:max个字符。',
        'unique' => '电话号码已存在。',
    ],
    'email' => [
        'required' => '需要电子邮件。',
        'string' => '电子邮件必须是字符串。',
        'email' => '电子邮件无效。',
        'max' => '电子邮件不得超过:max个字符。',
        'unique' => '电子邮件已存在。',
    ],
    'role' => [
        'required' => '请选择一个角色。',
        'invalid' => '角色无效。',
    ],
    'refer_code' => [
        'required' => '需要推荐码。',
        'invalid' => '推荐代码无效。',
    ],
    'disabled' => [
        'required' => '状态为必填项。',
        'boolean' => '状态无效。',
    ],
    'otp_invalid' => 'OTP 无效。',
    'user_id' => [
        'required' => '需要用户。',
        'string' => '用户必须是字符串。',
        'exists' => '用户不存在。',
    ],
    'token_invalid' => '令牌无效。',
    'amount' => [
        'required' => '金额为必填项。',
        'numeric' => '金额必须是数字。',
        'gt' => '金额必须大于 0。',
        'min' => '最小金额为:min。',
    ],
    'network' => [
        'required' => '需要网络。',
        'string' => '网络必须是字符串。',
        'in' => '网络无效。仅接受 BEP20 或 TRC20。',
    ],
];
