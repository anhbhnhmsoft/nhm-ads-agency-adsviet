<?php

return [
    'notifications' => [
        'activated' => 'Service package ":package" has been activated.',
        'failed' => 'Service package ":package" failed to activate. Please contact support.',
        'cancelled' => 'Service package ":package" has been cancelled.',
        'unknown_package' => 'Service package',
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

