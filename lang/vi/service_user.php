<?php

return [
    'notifications' => [
        'activated' => 'Gói dịch vụ ":package" đã được kích hoạt thành công.',
        'failed' => 'Gói dịch vụ ":package" kích hoạt thất bại. Vui lòng liên hệ hỗ trợ để được trợ giúp.',
        'cancelled' => 'Gói dịch vụ ":package" đã được hủy.',
        'unknown_package' => 'Gói dịch vụ',
    ],
    'payment_type' => [
        'prepay' => 'Trả trước',
        'postpay' => 'Trả sau',
    ],
    'telegram' => [
        'new_order_group_alert' => "🛎 <b>Đơn dịch vụ mới</b>\n<b>Mã đơn:</b> :order_code\n<b>Khách hàng:</b> :customer\n<b>Gói:</b> :package\n<b>Nền tảng:</b> :platform\n<b>Thanh toán:</b> :payment_type\n<b>Top-up:</b> :top_up_amount USD\n<b>Tổng tiền:</b> :total_cost USDT\n<b>Thời gian:</b> :time",
    ],
    'mail' => [
        'subject' => 'Thông báo trạng thái dịch vụ',
        'greeting' => 'Xin chào :user,',
        'content' => [
            'activated' => 'Gói dịch vụ ":package" đã được kích hoạt thành công.',
            'failed' => 'Gói dịch vụ ":package" kích hoạt thất bại. Vui lòng kiểm tra lại thông tin hoặc liên hệ hỗ trợ.',
            'cancelled' => 'Gói dịch vụ ":package" đã được hủy theo yêu cầu/kiểm tra của hệ thống.',
        ],
        'footer' => 'Trân trọng,',
    ],
];

