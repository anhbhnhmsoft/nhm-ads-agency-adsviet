<?php

return [
    'notifications' => [
        'activated' => '服务包“:package”已激活。',
        'failed' => '服务包“:package”激活失败。请联系支持人员。',
        'cancelled' => '服务包“:package”已被取消。',
        'unknown_package' => '服务包',
    ],
    'payment_type' => [
        'prepay' => '预付',
        'postpay' => '后付',
    ],
    'telegram' => [
        'new_order_group_alert' => "🛎 <b>新服务订单</b>\n<b>订单：</b>:order_code\n<b>客户：</b>:customer\n<b>套餐：</b>:package\n<b>平台：</b>:platform\n<b>付款：</b>:payment_type\n<b>充值：</b>:top_up_amount USD\n<b>总计：</b>:total_cost USDT\n<b>时间：</b>:time",
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
