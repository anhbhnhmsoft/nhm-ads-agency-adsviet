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
        'invalid_campaign_status' => 'Trạng thái chiến dịch không hợp lệ.',
        'invalid_budget_amount' => 'Ngân sách phải lớn hơn 0.',
        'failed_to_update_campaign_status' => 'Không thể cập nhật trạng thái chiến dịch trên Google Ads.',
        'failed_to_update_campaign_status_suspended' => 'Không thể cập nhật trạng thái chiến dịch vì tài khoản Google Ads đang bị tạm khóa (suspended). Vui lòng xử lý trạng thái tài khoản trực tiếp trên Google Ads trước.',
        'failed_to_update_campaign_budget' => 'Không thể cập nhật ngân sách chiến dịch trên Google Ads.',
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
    'telegram' => [
        'low_balance' => "⚠️ Cảnh báo số dư Google Ads thấp\n\nTài khoản \":accountName\" hiện chỉ còn :balance :currency (ngưỡng cảnh báo :threshold :currency).\nVui lòng nạp thêm tiền vào tài khoản Google Ads để tránh bị tạm dừng quảng cáo.",
    ],
];

