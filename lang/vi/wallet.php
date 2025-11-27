<?php

return [
    'network_not_configured' => 'Chưa cấu hình mạng nạp nào',
    'transaction_type' => [
        'unknown' => 'Không xác định',
        'deposit' => 'Nạp tiền',
        'withdraw' => 'Rút tiền',
        'refund' => 'Hoàn tiền',
        'fee' => 'Phí',
        'cashback' => 'Hoàn tiền thưởng',
        'service_purchase' => 'Mua dịch vụ',
    ],
    'transaction_status' => [
        'unknown' => 'Không xác định',
        'pending' => 'Chờ xử lý',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ],
    'flash' => [
        'deposit_created' => 'Lệnh nạp đã được tạo. Vui lòng thanh toán qua NowPayments.',
        'deposit_cancelled' => 'Đã hủy lệnh nạp.',
        'transaction_approved' => 'Xác thực giao dịch thành công',
        'transaction_cancelled' => 'Đã hủy giao dịch.',
        'withdraw_created' => 'Lệnh rút tiền đã được tạo. Vui lòng chờ admin xử lý.',
        'wallet_created' => 'Đã tạo ví cho người dùng.',
        'wallet_locked' => 'Đã khóa ví.',
        'wallet_unlocked' => 'Đã mở khóa ví.',
        'wallet_password_reset' => 'Đã đặt lại mật khẩu ví.',
        'wallet_password_changed' => 'Đã đổi mật khẩu ví.',
        'topup_success' => 'Nạp tiền thành công.',
        'withdraw_success' => 'Rút tiền thành công.',
        'deposit_checked' => 'Kiểm tra nạp tiền thành công.',
    ],
    'validation' => [
        'bank_name_required' => 'Vui lòng nhập tên ngân hàng hoặc ví điện tử',
        'bank_name_max' => 'Tên ngân hàng không được vượt quá :max ký tự',
        'account_holder_required' => 'Vui lòng nhập tên chủ tài khoản hoặc ví',
        'account_holder_max' => 'Tên chủ tài khoản không được vượt quá :max ký tự',
        'account_number_required' => 'Vui lòng nhập số tài khoản hoặc số điện thoại ví',
        'account_number_max' => 'Số tài khoản không được vượt quá :max ký tự',
        'wallet_need_configured' => 'Vui lòng cấu hình ví trước khi rút tiền',
        'transaction_invalid' => 'Giao dịch không hợp lệ',
        'transaction_failed' => 'Giao dịch đã bị từ chối',
        'transaction_expired' => 'Giao dịch đã hết hạn',
    ],
];


