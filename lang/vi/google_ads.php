<?php

return [
    'error' => [
        'service_not_found' => 'Dịch vụ không tồn tại hoặc bạn không có quyền truy cập.',
        'service_user_platform_not_google' => 'Dịch vụ không phải là 1 dịch vụ của Google Ads.',
        'missing_customer_ids' => 'Không tìm thấy danh sách tài khoản con Google Ads cho dịch vụ này.',
        'no_manager_id_found' => 'Không tìm thấy Business Manager ID trong cấu hình dịch vụ.',
        'sync_failed' => 'Đồng bộ dữ liệu Google Ads thất bại, vui lòng thử lại sau.',
        'campaign_not_found' => 'Không tìm thấy chiến dịch Google Ads.',
        'account_not_found' => 'Không tìm thấy tài khoản Google Ads tương ứng.',
        'failed_to_fetch_campaign_detail' => 'Không thể lấy chi tiết chiến dịch Google Ads.',
        'oauth_token_expired' => 'Token xác thực Google Ads đã hết hạn hoặc bị thu hồi. Vui lòng liên hệ quản trị viên để cập nhật lại thông tin đăng nhập.',
        'date_preset_invalid' => 'Khoảng thời gian không hợp lệ.',
    ],
    'account_status' => [
        'enabled' => 'Hoạt động',
        'canceled' => 'Đã hủy',
        'suspended' => 'Bị tạm khóa',
        'closed' => 'Đã đóng',
        'unknown' => 'Không xác định',
    ],
    'campaign_status' => [
        'enabled' => 'Đang chạy',
        'paused' => 'Đã tạm dừng',
        'removed' => 'Đã xóa',
        'unknown' => 'Không xác định',
    ],
    'account_status_messages' => [
        'canceled' => 'Tài khoản đã bị hủy. Vui lòng kiểm tra lại thông tin thanh toán hoặc liên hệ hỗ trợ.',
        'suspended' => 'Google đã tạm khóa tài khoản của bạn. Vui lòng kiểm tra cảnh báo và xử lý.',
        'closed' => 'Tài khoản đã đóng và không thể tiếp tục chạy quảng cáo.',
    ],
];

