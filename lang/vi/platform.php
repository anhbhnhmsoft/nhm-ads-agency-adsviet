<?php

return [
    'fields' => [
        'developer_token' => [
            'label' => 'Developer Token',
            'placeholder' => 'Nhập Developer Token từ Google Ads',
            'description' => 'Token được cấp từ Google Ads API',
        ],
        'client_id' => [
            'label' => 'Client ID',
            'placeholder' => 'xxxxx.apps.googleusercontent.com',
            'description' => 'OAuth 2.0 Client ID từ Google Cloud Console',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
            'placeholder' => 'Nhập Client Secret',
            'description' => 'OAuth 2.0 Client Secret từ Google Cloud Console',
        ],
        'refresh_token' => [
            'label' => 'Refresh Token',
            'placeholder' => 'Nhập Refresh Token',
            'description' => 'Refresh token từ OAuth flow',
        ],
        'login_customer_id' => [
            'label' => 'Login Customer ID (MCC)',
            'placeholder' => '1234567890',
            'description' => 'ID của MCC account',
        ],
        'customer_ids' => [
            'label' => 'Customer IDs (tùy chọn)',
            'placeholder' => 'Nhập danh sách Customer IDs, mỗi ID một dòng',
            'description' => 'Danh sách Customer IDs được quản lý (để trống nếu tất cả)',
        ],

        'app_id' => [
            'label' => 'App ID',
            'placeholder' => 'Nhập App ID từ Meta Developers',
            'description' => 'App ID từ Meta Developers Console',
        ],
        'app_secret' => [
            'label' => 'App Secret',
            'placeholder' => 'Nhập App Secret',
            'description' => 'App Secret từ Meta Developers Console',
        ],
        'access_token' => [
            'label' => 'Access Token',
            'placeholder' => 'Nhập Access Token (long-lived)',
            'description' => 'Long-lived access token hoặc System User Token',
        ],
        'business_manager_id' => [
            'label' => 'Business Manager ID',
            'placeholder' => '123456789012345',
            'description' => 'ID của Business Manager',
        ],
        'ad_account_ids' => [
            'label' => 'Ad Account IDs (tùy chọn)',
            'placeholder' => 'Nhập danh sách Ad Account IDs (act_xxx), mỗi ID một dòng',
            'description' => 'Danh sách Ad Account IDs được quản lý (để trống nếu tất cả)',
        ],
    ],
    'validation' => [
        'field_required' => 'Trường :field là bắt buộc.',
        'field_string' => 'Trường :field phải là chuỗi.',
        'field_array' => 'Trường :field phải là mảng.',
    ],
];


