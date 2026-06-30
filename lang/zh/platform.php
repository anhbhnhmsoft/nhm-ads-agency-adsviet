<?php

return [
    'fields' => [
        'developer_token' => [
            'label' => '开发者代币',
            'placeholder' => '输入 Google Ads 开发者令牌',
            'description' => 'Google Ads API 颁发的令牌',
        ],
        'client_id' => [
            'label' => '客户端ID',
            'placeholder' => 'xxxxx.apps.googleusercontent.com',
            'description' => '来自 Google Cloud Console 的 OAuth 2.0 客户端 ID',
        ],
        'client_secret' => [
            'label' => '客户秘密',
            'placeholder' => '输入客户端密码',
            'description' => '来自 Google Cloud Console 的 OAuth 2.0 客户端密钥',
        ],
        'refresh_token' => [
            'label' => '刷新令牌',
            'placeholder' => '输入刷新令牌',
            'description' => '从 OAuth 流刷新令牌',
        ],
        'login_customer_id' => [
            'label' => '登录客户 ID (MCC)',
            'placeholder' => '1234567890',
            'description' => 'MCC 帐户 ID',
        ],
        'customer_ids' => [
            'label' => '客户 ID（可选）',
            'placeholder' => '输入客户 ID，每行一个',
            'description' => '托管客户 ID 列表（全部留空）',
        ],

        'app_id' => [
            'label' => '应用程序ID',
            'placeholder' => '输入元开发人员提供的应用程序 ID',
            'description' => '来自 Meta 开发者控制台的应用程序 ID',
        ],
        'app_secret' => [
            'label' => '应用秘密',
            'placeholder' => '输入应用密码',
            'description' => '来自元开发者控制台的应用程序秘密',
        ],
        'access_token' => [
            'label' => '访问令牌',
            'placeholder' => '输入长期访问令牌',
            'description' => '当您需要 /me/businesses 列出 VIA 可以访问的每个业务组合时，请使用用户访问令牌。系统用户令牌仅限于发行它的企业。',
        ],
        'sync_all_accessible_businesses' => [
            'label' => '从用户令牌同步所有可访问的业务组合',
            'placeholder' => '',
            'description' => '启用后，同步将使用 /me/businesses 并忽略商务管理平台 ID 作为数据限制。',
        ],
        'business_manager_id' => [
            'label' => '业务经理 ID',
            'placeholder' => '留空以同步所有威盛业务组合',
            'description' => '仅当您有意将数据范围限定到一位业务管理平台时才使用此选项。',
        ],
        'ad_account_ids' => [
            'label' => '广告帐户 ID（可选）',
            'placeholder' => '输入广告帐户 ID (act_xxx)，每行一个',
            'description' => '托管广告帐户列表（全部留空）',
        ],
    ],
    'validation' => [
        'field_required' => '字段:field是必需的。',
        'field_string' => '字段:field必须是字符串。',
        'field_array' => '字段:field必须是数组。',
        'field_boolean' => '字段:field必须为 true 或 false。',
    ],
];
