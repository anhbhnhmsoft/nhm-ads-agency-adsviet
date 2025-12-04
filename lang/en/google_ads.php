<?php

return [
    'error' => [
        'service_not_found' => 'Service does not exist or you do not have permission.',
        'service_user_platform_not_google' => 'This service is not a Google Ads service.',
        'missing_customer_ids' => 'Google Ads sub accounts cannot be found for this service.',
        'no_manager_id_found' => 'Business Manager ID is missing in the service configuration.',
        'sync_failed' => 'Failed to sync Google Ads data. Please try again later.',
        'campaign_not_found' => 'Google Ads campaign not found.',
        'account_not_found' => 'Google Ads account not found.',
        'failed_to_fetch_campaign_detail' => 'Cannot fetch Google Ads campaign detail.',
        'oauth_token_expired' => 'Google Ads OAuth token expired or revoked. Please update the credentials.',
        'date_preset_invalid' => 'Invalid date range.',
    ],
    'account_status' => [
        'enabled' => 'Active',
        'canceled' => 'Canceled',
        'suspended' => 'Suspended',
        'closed' => 'Closed',
        'unknown' => 'Unknown',
    ],
    'campaign_status' => [
        'enabled' => 'Running',
        'paused' => 'Paused',
        'removed' => 'Removed',
        'unknown' => 'Unknown',
    ],
    'account_status_messages' => [
        'canceled' => 'The account was canceled. Please check the billing information or contact support.',
        'suspended' => 'Google has suspended your account. Please check the warnings and fix them.',
        'closed' => 'The account is closed and can no longer run ads.',
    ],
    'telegram' => [
        'low_balance' => "⚠️ Google Ads low balance alert\n\nAccount \":accountName\" only has :balance :currency left (threshold :threshold :currency).\nPlease top up your Google Ads account to avoid interruptions.",
    ],
];

