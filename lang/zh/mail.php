<?php

return [
    'verify_email_register' => [
        'subject' => '确认您的帐户注册',
        'greeting' => '你好:username',
        'thank_for_register' => '感谢您注册。这是您的验证码：:otp',
        'expire' => '此 OTP 将在:expire_time分钟后过期。',
        'footer' => '如果您没有提出此要求，请忽略该电子邮件。',
        'thanks' => '此致，',
    ],
    'verify_email_forgot_password' => [
        'subject' => '密码重置验证',
        'greeting' => '你好:username',
        'otp' => '这是您的密码重置代码：:otp',
        'expire' => '此 OTP 将在:expire_time分钟后过期。',
        'footer' => '如果您没有提出此要求，请忽略该电子邮件。',
        'thanks' => '此致，',
    ],
    'wallet_low_balance' => [
        'subject' => '钱包余额低提醒',
        'greeting' => '你好:username，',
        'description' => '您的钱包当前有:balanceUSDT（阈值:thresholdUSDT）。',
        'action' => '请充值以避免服务中断。',
        'thanks' => '此致，',
    ],
    'wallet_transaction' => [
        'subject' => '钱包交易通知',
        'greeting' => '你好:username，',
        'summary' => '您刚刚进行了一笔“:type”交易，金额为:amountUSDT。',
        'description' => '描述：:description',
        'thanks' => '此致，',
    ],
    'admin_wallet_transaction' => [
        'subject' => '客户钱包交易提醒',
        'greeting' => '你好:admin，',
        'summary' => '客户:customer执行了一笔“:type”交易，金额为:amountUSDT。',
        'stage_created' => '状态：已创建新的存款订单。',
        'stage_approved' => '状态：存款订单已被批准。',
        'description' => '描述：:description',
        'thanks' => '此致，',
    ],
    'google_ads_low_balance' => [
        'subject' => 'Google Ads 余额不足提醒',
        'greeting' => '你好:username，',
        'description' => '您的 Google Ads 帐户“:accountName”只剩下:balance:currency（阈值:threshold:currency）。',
        'action' => '请为您的 Google Ads 帐户充值，以免被暂停。',
        'thanks' => '此致，',
    ],
    'meta_ads_low_balance' => [
        'subject' => '元广告余额低警报',
        'greeting' => '你好:username，',
        'description' => '您的元广告帐户“:accountName”只剩下:balance:currency（阈值:threshold:currency）。',
        'action' => '请为Meta Business充值以避免活动中断。',
        'thanks' => '此致，',
    ],
];

