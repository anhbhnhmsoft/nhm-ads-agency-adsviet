<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'telegram' => [
        'bot_id' => env('TELEGRAM_BOT_ID'),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'url_telegram_auth' => env('TELEGRAM_URL_TELEGRAM_AUTH'),
        'support_group_id' => env('TELEGRAM_SUPPORT_GROUP_ID'),
        'timezone' => env('TELEGRAM_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    ],
    'binance' => [
        'key' => env('BINANCE_API_KEY'),
        'secret' => env('BINANCE_API_SECRET'),
        'base_url' => env('BINANCE_API_BASE_URL'),
    ],
    'nowpayments' => [
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'ipn_secret_key' => env('NOWPAYMENTS_IPN_SECRET_KEY'),
        'base_url' => env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io/v1'),
    ],
    'coinremitter' => [
        'base_url' => env('COINREMITTER_BASE_URL', 'https://api.coinremitter.com/v1'),
        'invoice_expire_minutes' => (int) env('COINREMITTER_INVOICE_EXPIRE_MINUTES', 30),
        'include_invoice_notify_url' => (bool) env('COINREMITTER_INCLUDE_INVOICE_NOTIFY_URL', false),
        'networks' => [
            'TRC20' => [
                'coin' => env('COINREMITTER_TRC20_COIN'),
                'api_key' => env('COINREMITTER_TRC20_API_KEY'),
                'password' => env('COINREMITTER_TRC20_PASSWORD'),
            ],
            'BEP20' => [
                'coin' => env('COINREMITTER_BEP20_COIN'),
                'api_key' => env('COINREMITTER_BEP20_API_KEY'),
                'password' => env('COINREMITTER_BEP20_PASSWORD'),
            ],
        ],
    ],
    'paymento' => [
        'base_url' => env('PAYMENTO_BASE_URL', 'https://api.paymento.io/v1'),
        'gateway_url' => env('PAYMENTO_GATEWAY_URL', 'https://app.paymento.io/gateway'),
        'api_key' => env('PAYMENTO_API_KEY'),
        'secret_key' => env('PAYMENTO_SECRET_KEY'),
        'expire_minutes' => (int) env('PAYMENTO_EXPIRE_MINUTES', 30),
        'speed' => (int) env('PAYMENTO_SPEED', 1),
    ],
    'exchange_rate' => [
        'base_url' => env('EXCHANGE_RATE_API_BASE_URL', 'https://open.er-api.com/v6/latest'),
        'target_currency' => env('EXCHANGE_RATE_TARGET_CURRENCY', 'USD'),
    ],
    'mobile_deep_link' => env('EXPO_DEEP_LINK'),
];
