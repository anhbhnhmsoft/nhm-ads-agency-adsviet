<?php

return [
    'verify_email_register' => [
        'subject' => 'Confirm your account registration',
        'greeting' => 'Hello :username',
        'thank_for_register' => 'Thank you for registering. This is your verification code: :otp',
        'expire' => 'This OTP will expire in :expire_time minutes.',
        'footer' => 'If you did not request this, please ignore the email.',
        'thanks' => 'Best regards,',
    ],
    'verify_email_forgot_password' => [
        'subject' => 'Password reset verification',
        'greeting' => 'Hello :username',
        'otp' => 'This is your password reset code: :otp',
        'expire' => 'This OTP will expire in :expire_time minutes.',
        'footer' => 'If you did not request this, please ignore the email.',
        'thanks' => 'Best regards,',
    ],
    'wallet_low_balance' => [
        'subject' => 'Wallet low balance alert',
        'greeting' => 'Hello :username,',
        'description' => 'Your wallet currently has :balance USDT (threshold :threshold USDT).',
        'action' => 'Please top up to avoid service disruption.',
        'thanks' => 'Best regards,',
    ],
    'wallet_transaction' => [
        'subject' => 'Wallet transaction notification',
        'greeting' => 'Hello :username,',
        'summary' => 'You just made a ":type" transaction with amount :amount USDT.',
        'description' => 'Description: :description',
        'thanks' => 'Best regards,',
    ],
    'admin_wallet_transaction' => [
        'subject' => 'Customer wallet transaction alert',
        'greeting' => 'Hello :admin,',
        'summary' => 'Customer :customer performed a ":type" transaction with amount :amount USDT.',
        'stage_created' => 'Status: A new deposit order has been created.',
        'stage_approved' => 'Status: The deposit order has been approved.',
        'description' => 'Description: :description',
        'thanks' => 'Best regards,',
    ],
    'google_ads_low_balance' => [
        'subject' => 'Google Ads low balance alert',
        'greeting' => 'Hello :username,',
        'description' => 'Your Google Ads account ":accountName" only has :balance :currency left (threshold :threshold :currency).',
        'action' => 'Please top up your Google Ads account to avoid suspension.',
        'thanks' => 'Best regards,',
    ],
    'meta_ads_low_balance' => [
        'subject' => 'Meta Ads low balance alert',
        'greeting' => 'Hello :username,',
        'description' => 'Your Meta Ads account ":accountName" only has :balance :currency left (threshold :threshold :currency).',
        'action' => 'Please top up Meta Business to avoid campaign interruption.',
        'thanks' => 'Best regards,',
    ],
];

