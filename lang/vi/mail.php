<?php


return [
    'verify_email_register' => [
        'subject' => 'Xác nhận đăng ký tài khoản',
        'greeting' => 'Xin chào :username',
        'thank_for_register' => 'Cảm ơn bạn đã đăng ký tài khoản. Đây là mã xác nhận của bạn: :otp',
        'expire' => 'Mã OTP này sẽ hết hạn sau :expire_time phút.',
        'footer' => 'Nếu bạn không yêu cầu, vui lòng bỏ qua email này.',
        'thanks' => 'Trân trọng,',
    ],
    'verify_email_forgot_password' => [
        'subject' => 'Xác nhận đặt lại mật khẩu',
        'greeting' => 'Xin chào :username',
        'otp' => 'Đây là mã xác nhận đặt lại mật khẩu của bạn: :otp',
        'expire' => 'Mã OTP này sẽ hết hạn sau :expire_time phút.',
        'footer' => 'Nếu bạn không yêu cầu, vui lòng bỏ qua email này.',
        'thanks' => 'Trân trọng,',
    ],
    'wallet_low_balance' => [
        'subject' => 'Cảnh báo số dư ví thấp',
        'greeting' => 'Xin chào :username,',
        'description' => 'Ví của bạn hiện chỉ còn :balance USDT (ngưỡng cảnh báo là :threshold USDT).',
        'action' => 'Vui lòng nạp thêm để tránh gián đoạn dịch vụ.',
        'thanks' => 'Trân trọng,',
    ],
    'wallet_transaction' => [
        'subject' => 'Thông báo giao dịch ví',
        'greeting' => 'Xin chào :username,',
        'summary' => 'Bạn vừa có giao dịch ":type" với số tiền :amount USDT.',
        'description' => 'Mô tả: :description',
        'thanks' => 'Trân trọng,',
    ],
    'admin_wallet_transaction' => [
        'subject' => 'Thông báo giao dịch ví của khách',
        'greeting' => 'Xin chào :admin,',
        'summary' => 'Khách hàng :customer vừa có giao dịch ":type" với số tiền :amount USDT.',
        'stage_created' => 'Tình trạng: Lệnh nạp mới vừa được tạo.',
        'stage_approved' => 'Tình trạng: Lệnh nạp đã được duyệt thành công.',
        'description' => 'Mô tả: :description',
        'thanks' => 'Trân trọng,',
    ],
    'google_ads_low_balance' => [
        'subject' => 'Cảnh báo số dư Google Ads thấp',
        'greeting' => 'Xin chào :username,',
        'description' => 'Tài khoản Google Ads ":accountName" của bạn hiện chỉ còn :balance :currency (ngưỡng cảnh báo là :threshold :currency).',
        'action' => 'Vui lòng nạp thêm tiền vào tài khoản Google Ads để tránh bị tạm dừng quảng cáo.',
        'thanks' => 'Trân trọng,',
    ],
    'meta_ads_low_balance' => [
        'subject' => 'Cảnh báo số dư Meta Ads thấp',
        'greeting' => 'Xin chào :username,',
        'description' => 'Tài khoản Meta Ads ":accountName" của bạn hiện chỉ còn :balance :currency (ngưỡng cảnh báo là :threshold :currency).',
        'action' => 'Vui lòng nạp thêm tiền vào Meta Business để tránh bị tạm dừng quảng cáo.',
        'thanks' => 'Trân trọng,',
    ],
];
