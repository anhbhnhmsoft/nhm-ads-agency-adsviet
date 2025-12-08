<?php

return [
    'title' => 'Hỗ trợ',
    'list' => 'Danh sách yêu cầu hỗ trợ',
    'create' => 'Tạo yêu cầu hỗ trợ',
    'detail' => 'Chi tiết yêu cầu hỗ trợ',
    'not_found' => 'Không tìm thấy yêu cầu hỗ trợ',
    'create_success' => 'Đã tạo yêu cầu hỗ trợ thành công',
    'message_sent' => 'Đã gửi tin nhắn thành công',
    'status_updated' => 'Đã cập nhật trạng thái thành công',

    'status' => [
        'pending' => 'Chờ xử lý',
        'open' => 'Đang mở',
        'in_progress' => 'Đang xử lý',
        'resolved' => 'Đã giải quyết',
        'closed' => 'Đã đóng',
    ],

    'priority' => [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ],

    'reply_side' => [
        'customer' => 'Khách hàng',
        'staff' => 'Nhân viên',
    ],

    'subject' => 'Chủ đề',
    'description' => 'Mô tả',
    'priority_label' => 'Mức độ ưu tiên',
    'status_label' => 'Trạng thái',
    'created_by' => 'Người tạo',
    'assigned_to' => 'Người xử lý',
    'created_at' => 'Ngày tạo',
    'updated_at' => 'Cập nhật lần cuối',
    'messages' => 'Tin nhắn',
    'add_message' => 'Thêm tin nhắn',
    'message_placeholder' => 'Nhập tin nhắn của bạn...',
    'send' => 'Gửi',
    'update_status' => 'Cập nhật trạng thái',
    'no_tickets' => 'Chưa có yêu cầu hỗ trợ nào',
    'no_messages' => 'Chưa có tin nhắn nào',
    'telegram_notification_failed' => 'Không thể gửi thông báo Telegram',

    'validation' => [
        'subject_required' => 'Vui lòng nhập chủ đề',
        'subject_string' => 'Chủ đề phải là chuỗi',
        'subject_max' => 'Chủ đề không được vượt quá :max ký tự',
        'description_required' => 'Vui lòng nhập mô tả',
        'description_string' => 'Mô tả phải là chuỗi',
        'description_max' => 'Mô tả không được vượt quá :max ký tự',
        'message_required' => 'Vui lòng nhập tin nhắn',
        'message_string' => 'Tin nhắn phải là chuỗi',
        'message_max' => 'Tin nhắn không được vượt quá :max ký tự',
        'priority_integer' => 'Mức độ ưu tiên phải là số nguyên',
        'priority_invalid' => 'Mức độ ưu tiên không hợp lệ',
        'status_required' => 'Vui lòng chọn trạng thái',
        'status_integer' => 'Trạng thái phải là số nguyên',
        'status_invalid' => 'Trạng thái không hợp lệ',
    ],

    'transfer' => [
        'from_account_required' => 'Vui lòng chọn tài khoản chuyển đi',
        'to_account_required' => 'Vui lòng chọn tài khoản nhận',
        'amount_required' => 'Vui lòng nhập số tiền',
        'amount_numeric' => 'Số tiền phải là số',
        'amount_min' => 'Số tiền phải lớn hơn 0',
        'notes_max' => 'Ghi chú không được vượt quá :max ký tự',
        'create_success' => 'Đã tạo yêu cầu chuyển tiền thành công',
    ],
];

