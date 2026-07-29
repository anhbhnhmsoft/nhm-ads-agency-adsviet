<?php

return [
    'notifications' => [
        'activated' => 'Service package ":package" has been activated.',
        'failed' => 'Service package ":package" failed to activate. Please contact support.',
        'cancelled' => 'Service package ":package" has been cancelled.',
        'unknown_package' => 'Service package',
    ],
    'payment_type' => [
        'prepay' => 'Prepay',
        'postpay' => 'Postpay',
    ],
    'telegram' => [
        'new_order_group_alert' => "🛎 <b>New service order</b>\n<b>Order:</b> :order_code\n<b>Customer:</b> :customer\n<b>Package:</b> :package\n<b>Platform:</b> :platform\n<b>Payment:</b> :payment_type\n<b>Top-up:</b> :top_up_amount USD\n<b>Total:</b> :total_cost USDT\n<b>Time:</b> :time",
    ],
    'mail' => [
        'subject' => 'Service status notification',
        'greeting' => 'Hello :user,',
        'content' => [
            'activated' => 'Service package ":package" has been activated successfully.',
            'failed' => 'Service package ":package" failed to activate. Please verify the information or contact support.',
            'cancelled' => 'Service package ":package" has been cancelled by request/system check.',
        ],
        'footer' => 'Best regards,',
    ],
];
