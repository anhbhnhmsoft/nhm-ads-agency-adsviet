-- Database Schema Documentation


# bảng users
    # cấu trúc
    - id (int, primary key, auto-increment)
    - name (varchar, not null) -- tên hiển thị
    - username (varchar, unique, not null) -- tên đăng nhập (có thể là email hoặc số điện thoại)
    - email (varchar, unique, nullable) -- email người dùng nếu người dùng ko đăng ký qua telegram thì phải cần xác thực email
    - email_verified_at (timestamp, nullable) -- thời gian xác thực email
    - phone (varchar, unique, nullable) -- số điện thoại
    - password (varchar, not null)
    - role (smallint, not null) -- vai trò (trong enum UserRole)
    - disabled (boolean, not null, default false) -- trạng thái
    - telegram_id (varchar, unique, nullable) -- id telegram
    - whatsapp_id (varchar, unique, nullable) -- id whatsapp
    - referral_code (varchar, unique, not null) -- mã giới thiệu
    - rememberToken
    - softDeletes
    - timestamps

# bảng personal_access_tokens
    # note
    - lưu trữ thông tin token truy cập của người dùng
    - là bảng có sẵn trong Laravel Sanctum

# bảng user_referrals
    # note
    - lưu trữ thông tin giới thiệu người dùng
    - Nếu là role employee, manager → nhân viên sẽ quản lý user mới này và + vào doanh số của employee
    - Nếu role Agency → user mới sẽ thuộc quyền quản lý của Agency
    - Còn nếu đối với admin + customer → Ref code ko hợp lệ

    # quan hệ
    - n-1 với bảng users qua referrer_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - referrer_id (int, foreign key to users.id, not null) -- người giới thiệu
    - referred_id (int, foreign key to users.id, not null) -- người được giới thiệu
    - softDeletes
    - timestamps

# bảng user_otp
    # note
    - lưu trữ thông tin OTP cho người dùng
    # quan hệ
    - n-1 với bảng users qua user_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - user_id (int, foreign key to users.id, not null)
    - code (varchar, not null) -- mã OTP
    - type (smallint, not null) -- loại OTP (trong enum OtpType)
    - expires_at (datetime, not null) -- thời gian hết hạn

# bảng user_devices
    # note
    - lưu trữ thông tin thiết bị người dùng
    # quan hệ
    - n-1 với bảng users qua user_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - user_id (int, foreign key to users.id, not null)
    - device_id (varchar, not null, index) -- mã thiết bị
    - device_name (varchar, nullable) -- tên thiết bị
    - device_type (smallint, not null) -- loại thiết bị (ví dụ: iOS, Android, Web)
    - ip (varchar, nullable) -- địa chỉ IP
    - notification_token (varchar, nullable) -- token thông báo
    - last_active_at (datetime, not null) -- thời gian hoạt động cuối cùng
    - softDeletes
    - timestamps


# bảng user_wallets
    # note
    - mỗi user có 1 ví
    - Tiền tệ chính USDT
    # quan hệ
    - 1-1 với bảng users qua user_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - user_id (int, foreign key to users.id, not null)
    - balance (decimal(18, 8), not null, default 0) -- số dư ví
    - password (varchar, null) -- mật khẩu ví
    - status (smallint, not null, default 0) -- trạng thái ví (trong enum WalletStatus)
    - softDeletes
    - timestamps

# bảng user_wallet_transactions
    # quan hệ
    - n-1 với bảng user_wallets qua wallet_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - wallet_id (int, foreign key to user_wallets.id, not null)
    - amount (decimal(18, 8), not null) -- số tiền giao dịch
    - type (smallint, not null) -- loại giao dịch (trong enum WalletTransactionType)
    - status (smallint, not null) -- trạng thái giao dịch (trong enum WalletTransactionStatus)
    - reference_id (varchar, nullable) -- mã tham chiếu bên ngoài (nếu có) 
    - description (varchar, nullable) -- mô tả giao dịch
    - network (varchar, nullable) -- mạng nạp (BEP20/TRC20)
    - tx_hash (varchar, nullable) -- hash giao dịch on-chain (nếu có)
    - customer_name (varchar, nullable) -- tên khách hàng
    - customer_email (varchar, nullable) -- email khách hàng
    - deposit_address (varchar, nullable) -- địa chỉ ví nhận tiền
    - payment_id (varchar, nullable) -- NowPayments payment ID
    - pay_address (varchar, nullable) -- địa chỉ ví từ NowPayments để nhận thanh toán
    - expires_at (datetime, nullable) -- thời gian hết hạn lệnh nạp (15 phút sau khi tạo)
    - softDeletes
    - timestamps

# bảng user_wallet_transaction_logs
    # quan hệ
    - n-1 với bảng user_wallet_transactions qua transaction_id

    # cấu trúc
    - id (int, primary key, auto-increment)
    - transaction_id (int, foreign key to user_wallet_transactions.id, not null)
    - previous_status (smallint, not null) -- trạng thái trước khi thay đổi
    - new_status (smallint, not null) -- trạng thái sau khi thay đổi
    - changed_at (datetime, not null) -- thời gian thay đổi trạng thái
    - description (varchar, nullable) -- mô tả thay đổi trạng thái
    - softDeletes
    - timestamps

# platform_settings
    # note
    - lưu trữ các cài đặt cấu hình của nền tảng google ads và meta ads
    - Toggle active sẽ ảnh hưởng tới tất cả user client đang sử dụng của hệ thống

    # cấu trúc
    - id (int, primary key, auto-increment)
    - platform (smallint, not null) -- loại nền tảng (trong enum Platform)
    - config (text, not null) -- cấu hình cài đặt (json format - mã hóa)
    - disabled (boolean, not null, default false) -- trạng thái
    - softDeletes
    - timestamps

# bảng service_packages
    # note
    - lưu trữ các gói dịch vụ của hệ thống
    - Mỗi gói dịch vụ sẽ có các tính năng khác nhau
    # cấu trúc
    - id (int, primary key, auto-increment)
    - name (varchar, not null) -- tên gói dịch vụ
    - description (text, nullable) -- mô tả gói dịch vụ
    - platform (smallint, not null) -- nền tảng (trong enum Platform - google ads hoặc meta ads)
    - features (text, not null) -- các tính năng của gói dịch vụ (json format)
    - open_fee (decimal(18, 8), not null) -- giá mở tài khoản
    - range_min_top_up (decimal(18, 8), not null) -- số dư tối thiểu cần phải nạp tiền để có thể sử dụng gói dịch vụ
    - top_up_fee (smallint, not null, default 0) -- % phí nạp tiền
    - set_up_time (int, not null) -- thời gian thiết lập (tính bằng giờ)
    - disabled (boolean, not null, default false) -- trạng thái
    - softDeletes
    - timestamps

# bảng service_users
    # note
    - lưu trữ thông tin người dùng sử dụng gói dịch vụ
    # quan hệ
    - n-1 với bảng service_packages qua package_id
    - n-1 với bảng users qua user_id
     # cấu trúc
    - id (int, primary key, auto-increment)
    - package_id (int, foreign key to service_packages.id, not null)
    - user_id (int, foreign key to users.id, not null)
    - config_account (json, not null) -- cấu hình tài khoản dịch vụ (json format)
    - status (smallint, not null) -- trạng thái dịch vụ (trong enum ServiceUserStatus)
    - budget (decimal(18, 8), not null, default 0) -- ngân sách dịch vụ
    - description (varchar, nullable) -- mô tả thêm
    - softDeletes
    - timestamps


# bảng service_user_transaction_logs
    # note
    - lưu trữ thông tin các giao dịch của người dùng sử dụng gói dịch vụ
    # quan hệ
    - n-1 với bảng service_users qua service_user_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - service_user_id (int, foreign key to service_users.id, not null)
    - amount (decimal(18, 8), not null) -- số tiền giao dịch
    - type (smallint, not null) -- loại giao dịch (trong enum ServiceUserTransactionType)
    - status (smallint, not null) -- trạng thái giao dịch (trong enum ServiceUserTransactionStatus)
    - reference_id (varchar, nullable) -- mã tham chiếu bên ngoài (nếu có) 
    - description (varchar, nullable) -- mô tả giao dịch
    - softDeletes
    - timestamps

# bảng campaigns
    # note
    - lưu trữ thông tin các chiến dịch của người dùng sử dụng gói dịch vụ
    # quan hệ
    - n-1 với bảng service_users qua service_user_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - service_user_id (int, foreign key to service_users.id, not null)
    - name (varchar, not null) -- tên chiến dịch
    - platform (smallint, not null) -- nền tảng (trong enum Platform)
    - config (text, not null) -- cấu hình chiến dịch (json format - mã hóa)
    - status (smallint, not null) -- trạng thái chiến dịch (trong enum ServiceUserCampaignStatus)
    - budget (decimal(18, 8), not null, default 0) -- ngân sách chiến dịch
    - target_audience (text, nullable) -- đối tượng mục tiêu (json format)
    - start_date (date, nullable) -- ngày bắt đầu chạy chiến dịch
    - end_date (date, nullable) -- ngày kết thúc chạy chiến dịch
    - description (varchar, nullable) -- mô tả thêm
    - softDeletes
    - timestamps

# bảng campaign_creatives
    # note
    - lưu trữ thông tin các nội dung sáng tạo (hình ảnh, video, văn bản) của chiến dịch
    # quan hệ
    - n-1 với bảng campaigns qua campaign_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - campaign_id (int, foreign key to campaigns.id, not null)
    - type (smallint, not null) -- loại nội dung (trong enum CampaignCreativeType)
    - title (varchar, not null) -- tiêu đề nội dung
    - content (text, not null) -- nội dung sáng tạo
    - status (smallint, not null) -- trạng thái nội dung (trong enum CampaignCreativeStatus)
    - softDeletes
    - timestamps

# bảng campaign_creative_files
    # note
    - lưu trữ thông tin các tệp tin liên quan đến nội dung sáng tạo (hình ảnh, video)
    # quan hệ
    - n-1 với bảng campaign_creatives qua creative_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - creative_id (int, foreign key to campaign_creatives.id, not null)
    - file_path (varchar, not null) -- đường dẫn tệp tin
    - file_type (varchar, not null) -- loại tệp tin (ví dụ: image/jpeg, video/mp4)
    - file_size (int, not null) -- kích thước tệp tin (tính bằng byte)
    - file_name (varchar, not null) -- tên tệp tin
    - softDeletes
    - timestamps

# bảng campaign_performance_logs
    # note
    - lưu trữ thông tin hiệu suất chiến dịch theo ngày
    # quan hệ
    - n-1 với bảng campaigns qua campaign_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - campaign_id (int, foreign key to campaigns.id, not null)
    - date (date, not null) -- ngày ghi nhận hiệu suất
    - impressions (int, not null, default 0) -- số lần hiển thị
    - clicks (int, not null, default 0) -- số lần nhấp
    - conversions (int, not null, default 0) -- số lần chuyển đổi
    - cost (decimal(18, 8), not null, default 0) -- chi phí đã sử dụng
    - softDeletes
    - timestamps

# bảng tickets
    # note
    - lưu trữ thông tin các vé hỗ trợ khách hàng
    # quan hệ
    - n-1 với bảng users qua user_id (người tạo vé)
    - n-1 với bảng users qua assigned_to (người được giao xử lý vé)
    # cấu trúc
    - id (int, primary key, auto-increment)
    - user_id (int, foreign key to users.id, not null)
    - subject (varchar, not null) -- chủ đề
    - description (text, not null) -- mô tả vấn đề
    - status (smallint, not null, default 0) -- trạng thái vé (trong enum TicketStatus)
    - priority (smallint, not null, default 0) -- mức độ ưu tiên (trong enum TicketPriority)
    - assigned_to (int, foreign key to users.id, nullable) -- người được giao xử lý vé
    - softDeletes
    - timestamps

# bảng ticket_conversations
    # note
    - lưu trữ thông tin các cuộc trò chuyện trong vé hỗ trợ khách hàng
    # quan hệ
    - n-1 với bảng tickets qua ticket_id
    - n-1 với bảng users qua user_id (người nhắn)
    # cấu trúc
    - id (int, primary key, auto-increment)
    - ticket_id (int, foreign key to tickets.id, not null)
    - user_id (int, foreign key to users.id, not null)
    - message (text, not null) -- nội dung tin nhắn
    - attachment (varchar, nullable) -- đường dẫn tệp đính kèm (nếu có)
    - reply_side (smallint, not null) -- bên trả lời (trong enum TicketReplySide)
    - timestamps
    - softDeletes


# bảng notifications
    # note
    - lưu trữ thông tin các thông báo gửi đến người dùng
    # quan hệ
    - n-1 với bảng users qua user_id
    # cấu trúc
    - id (int, primary key, auto-increment)
    - user_id (int, foreign key to users.id, not null)
    - title (varchar, not null) -- tiêu đề thông báo
    - description (text, not null) -- nội dung thông báo
    - data (text, nullable) -- dữ liệu bổ sung (json format)
    - type (smallint, not null) -- loại thông báo (trong enum NotificationType)
    - status (smallint, not null, default 0) -- trạng thái thông báo (trong enum NotificationStatus)
    - softDeletes
    - timestamps

# bảng configs
    # note
    - lưu trữ các cấu hình chung của hệ thống
    # cấu trúc
    - id (int, primary key, auto-increment)
    - key (varchar, unique, not null) -- khóa cấu hình
    - type (smallint, not null) -- loại cấu hình (trong enum ConfigType)
    - value (text, not null) -- giá trị cấu hình 
    - description (varchar, nullable) -- mô tả cấu hình
    - softDeletes
    - timestamps
