<?php

return [
    'notifications' => [
        'activated' => '服务包“:package”已激活。',
        'failed' => '服务包“:package”激活失败。请联系支持人员。',
        'cancelled' => '服务包“:package”已被取消。',
        'unknown_package' => '服务包',
    ],
    'mail' => [
        'subject' => 'Service status notification',
        'greeting' => '你好:user，',
        'content' => [
            'activated' => '服务包“:package”已成功激活。',
            'failed' => '服务包“:package”激活失败。请验证信息或联系支持人员。',
            'cancelled' => '服务包“:package”已通过请求/系统检查取消。',
        ],
        'footer' => '此致，',
    ],
];

