<?php

return [
    'fields' => [
        'developer_token' => [
            'label' => 'Developer Token',
            'placeholder' => 'Enter the Google Ads Developer Token',
            'description' => 'Token issued by the Google Ads API',
        ],
        'client_id' => [
            'label' => 'Client ID',
            'placeholder' => 'xxxxx.apps.googleusercontent.com',
            'description' => 'OAuth 2.0 Client ID from Google Cloud Console',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
            'placeholder' => 'Enter Client Secret',
            'description' => 'OAuth 2.0 Client Secret from Google Cloud Console',
        ],
        'refresh_token' => [
            'label' => 'Refresh Token',
            'placeholder' => 'Enter Refresh Token',
            'description' => 'Refresh token from the OAuth flow',
        ],
        'login_customer_id' => [
            'label' => 'Login Customer ID (MCC)',
            'placeholder' => '1234567890',
            'description' => 'ID of the MCC account',
        ],
        'customer_ids' => [
            'label' => 'Customer IDs (optional)',
            'placeholder' => 'Enter customer IDs, one per line',
            'description' => 'List of managed customer IDs (leave blank for all)',
        ],

        'app_id' => [
            'label' => 'App ID',
            'placeholder' => 'Enter App ID from Meta Developers',
            'description' => 'App ID from Meta Developers Console',
        ],
        'app_secret' => [
            'label' => 'App Secret',
            'placeholder' => 'Enter App Secret',
            'description' => 'App Secret from Meta Developers Console',
        ],
        'access_token' => [
            'label' => 'Access Token',
            'placeholder' => 'Enter long-lived access token',
            'description' => 'Use a User Access Token when you need /me/businesses to list every business portfolio the VIA can access. A System User token is limited to the business that issued it.',
        ],
        'sync_all_accessible_businesses' => [
            'label' => 'Sync all accessible business portfolios from User token',
            'placeholder' => '',
            'description' => 'When enabled, sync uses /me/businesses and ignores Business Manager ID as a data limit.',
        ],
        'business_manager_id' => [
            'label' => 'Business Manager ID',
            'placeholder' => 'Leave blank to sync all VIA business portfolios',
            'description' => 'Only use this when you intentionally want to scope data to one Business Manager.',
        ],
        'ad_account_ids' => [
            'label' => 'Ad Account IDs (optional)',
            'placeholder' => 'Enter Ad Account IDs (act_xxx), one per line',
            'description' => 'List of managed ad accounts (leave blank for all)',
        ],
    ],
    'validation' => [
        'field_required' => 'Field :field is required.',
        'field_string' => 'Field :field must be a string.',
        'field_array' => 'Field :field must be an array.',
        'field_boolean' => 'Field :field must be true or false.',
    ],
];
