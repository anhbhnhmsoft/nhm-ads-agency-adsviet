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
            'description' => 'Dùng User Access Token của VIA nếu cần lấy tất cả Business portfolios bằng /me/businesses. System User Token chỉ thấy dữ liệu trong BM được cấp.',
        ],
        'sync_all_accessible_businesses' => [
            'label' => 'Đồng bộ tất cả Business portfolios của User token',
            'placeholder' => '',
            'description' => 'Bật mục này để gọi /me/businesses và lấy toàn bộ BM mà VIA/User token truy cập được. Khi bật, Business Manager ID bên dưới chỉ để ghi chú và không dùng để giới hạn dữ liệu.',
        ],
        'business_manager_id' => [
            'label' => 'Business Manager ID',
            'placeholder' => 'Để trống nếu muốn lấy tất cả BM của VIA',
            'description' => 'Chỉ nhập khi muốn khóa dữ liệu trong một BM cụ thể. Để lấy giống SMIT, hãy bật đồng bộ tất cả và dùng User Access Token.',
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
        'field_boolean' => 'Trường :field phải là đúng/sai.',
    ],
];
