<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bảng users - Lưu trữ thông tin người dùng
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên hiển thị');
            $table->string('username')->unique()->comment('Tên đăng nhập (có thể là email hoặc số điện thoại)');
            $table->string('phone')->nullable()->unique()->comment('Số điện thoại');
            $table->string('password')->comment('Mật khẩu');
            $table->smallInteger('role')->comment('Vai trò (trong enum UserRole)');
            $table->boolean('disabled')->default(false)->comment('Trạng thái');
            $table->string('telegram_id')->unique()->nullable()->comment('ID Telegram');
            $table->string('whatsapp_id')->unique()->nullable()->comment('ID WhatsApp');
            $table->string('referral_code')->unique()->comment('Mã giới thiệu');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade')->comment('Người giới thiệu');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade')->comment('Người được giới thiệu');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_otp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code')->comment('Mã OTP');
            $table->smallInteger('type')->comment('Loại OTP (trong enum OtpType)');
            $table->dateTime('expires_at')->comment('Thời gian hết hạn');
            $table->timestamps();
        });

        // Bảng user_devices - Lưu trữ thông tin thiết bị người dùng
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id')->unique()->comment('Mã thiết bị');
            $table->smallInteger('device_type')->comment('Loại thiết bị (ví dụ: iOS, Android, Web)');
            $table->boolean('active')->default(true)->comment('Trạng thái hoạt động của thiết bị');
            $table->dateTime('last_active_at')->comment('Thời gian hoạt động cuối cùng');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng user_wallets - Mỗi user có 1 ví, tiền tệ chính USDT
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 18, 8)->default(0)->comment('Số dư ví');
            $table->string('password')->nullable()->comment('Mật khẩu ví');
            $table->smallInteger('status')->default(0)->comment('Trạng thái ví (trong enum WalletStatus)');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng user_wallet_transactions - Lưu trữ giao dịch ví
        Schema::create('user_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('user_wallets')->onDelete('cascade');
            $table->decimal('amount', 18, 8)->comment('Số tiền giao dịch');
            $table->smallInteger('type')->comment('Loại giao dịch (trong enum WalletTransactionType)');
            $table->smallInteger('status')->comment('Trạng thái giao dịch (trong enum WalletTransactionStatus)');
            $table->string('reference_id')->nullable()->comment('Mã tham chiếu bên ngoài (nếu có)');
            $table->string('description')->nullable()->comment('Mô tả giao dịch');
            $table->string('network')->nullable()->comment('Mạng nạp (BEP20/TRC20)');
            $table->string('tx_hash')->nullable()->comment('Hash giao dịch on-chain (nếu có)');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('deposit_address')->nullable()->comment('Địa chỉ ví nhận tiền');
            $table->string('payment_id')->nullable()->comment('NowPayments payment ID');
            $table->string('pay_address')->nullable()->comment('Địa chỉ ví từ NowPayments để nhận thanh toán');
            $table->dateTime('expires_at')->nullable()->comment('Thời gian hết hạn lệnh nạp (15 phút sau khi tạo)');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng user_wallet_transaction_logs - Lưu trữ lịch sử thay đổi trạng thái giao dịch
        Schema::create('user_wallet_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('user_wallet_transactions')->onDelete('cascade');
            $table->smallInteger('previous_status')->comment('Trạng thái trước khi thay đổi');
            $table->smallInteger('new_status')->comment('Trạng thái sau khi thay đổi');
            $table->dateTime('changed_at')->comment('Thời gian thay đổi trạng thái');
            $table->string('description')->nullable()->comment('Mô tả thay đổi trạng thái');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng platform_settings - Lưu trữ các cài đặt cấu hình của nền tảng Google Ads và Meta Ads
        // Toggle active sẽ ảnh hưởng tới tất cả user client đang sử dụng của hệ thống
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('platform')->comment('Loại nền tảng (trong enum Platform)');
            $table->text('config')->comment('Cấu hình cài đặt (json format - mã hóa)');
            $table->boolean('disabled')->default(false)->comment('Trạng thái');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng service_packages - Lưu trữ các gói dịch vụ của hệ thống
        // Mỗi gói dịch vụ sẽ có các tính năng khác nhau
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên gói dịch vụ');
            $table->foreignId('platform_setting_id')
                ->constrained('platform_settings')->onDelete('cascade');
            $table->smallInteger('platform')->comment('Nền tảng (trong enum Platform)');
            $table->text('features')->comment('Các tính năng của gói dịch vụ (json format)');
            $table->decimal('open_fee', 18, 8)->comment('Giá mở tài khoản');
            $table->decimal('top_up_fee', 18, 8)->comment('% phí nạp tiền');
            $table->integer('set_up_time')->comment('Thời gian thiết lập (tính bằng phút)');
            $table->boolean('disabled')->default(false)->comment('Trạng thái');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng service_package_fee_tiers - Lưu trữ các mức phí theo gói dịch vụ
        // Mỗi gói dịch vụ có thể có nhiều mức phí khác nhau dựa trên số dư tài khoản quảng cáo
        Schema::create('service_package_fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('service_packages')->onDelete('cascade');
            $table->decimal('range_min', 18, 8)->comment('Số dư tối thiểu');
            $table->decimal('range_max', 18, 8)->comment('Số dư tối đa');
            $table->decimal('fee_percent', 5, 2)->comment('% phí áp dụng');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng service_users - Lưu trữ thông tin người dùng sử dụng gói dịch vụ
        Schema::create('service_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('service_packages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('config_account')->comment('Cấu hình tài khoản dịch vụ (json format - mã hóa)');
            $table->smallInteger('status')->comment('Trạng thái dịch vụ (trong enum ServiceUserStatus)');
            $table->decimal('budget', 18, 8)->default(0)->comment('Ngân sách dịch vụ');
            $table->string('description')->nullable()->comment('Mô tả thêm');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng service_user_transaction_logs - Lưu trữ thông tin các giao dịch của người dùng sử dụng gói dịch vụ
        Schema::create('service_user_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_user_id')->constrained('service_users')->onDelete('cascade');
            $table->decimal('amount', 18, 8)->comment('Số tiền giao dịch');
            $table->smallInteger('type')->comment('Loại giao dịch (trong enum ServiceUserTransactionType)');
            $table->smallInteger('status')->comment('Trạng thái giao dịch (trong enum ServiceUserTransactionStatus)');
            $table->string('reference_id')->nullable()->comment('Mã tham chiếu bên ngoài (nếu có)');
            $table->string('description')->nullable()->comment('Mô tả giao dịch');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng campaigns - Lưu trữ thông tin các chiến dịch của người dùng sử dụng gói dịch vụ
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_user_id')->constrained('service_users')->onDelete('cascade');
            $table->string('name')->comment('Tên chiến dịch');
            $table->smallInteger('platform')->comment('Nền tảng (trong enum Platform)');
            $table->text('config')->comment('Cấu hình chiến dịch (json format - mã hóa)');
            $table->smallInteger('status')->comment('Trạng thái chiến dịch (trong enum ServiceUserCampaignStatus)');
            $table->decimal('budget', 18, 8)->default(0)->comment('Ngân sách chiến dịch');
            $table->text('target_audience')->nullable()->comment('Đối tượng mục tiêu (json format)');
            $table->date('start_date')->nullable()->comment('Ngày bắt đầu chạy chiến dịch');
            $table->date('end_date')->nullable()->comment('Ngày kết thúc chạy chiến dịch');
            $table->string('description')->nullable()->comment('Mô tả thêm');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng campaign_creatives - Lưu trữ thông tin các nội dung sáng tạo (hình ảnh, video, văn bản) của chiến dịch
        Schema::create('campaign_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->smallInteger('type')->comment('Loại nội dung (trong enum CampaignCreativeType)');
            $table->string('title')->comment('Tiêu đề nội dung');
            $table->text('content')->comment('Nội dung sáng tạo');
            $table->smallInteger('status')->comment('Trạng thái nội dung (trong enum CampaignCreativeStatus)');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng campaign_creative_files - Lưu trữ thông tin các tệp tin liên quan đến nội dung sáng tạo (hình ảnh, video)
        Schema::create('campaign_creative_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creative_id')->constrained('campaign_creatives')->onDelete('cascade');
            $table->string('file_path')->comment('Đường dẫn tệp tin');
            $table->string('file_type')->comment('Loại tệp tin (ví dụ: image/jpeg, video/mp4)');
            $table->integer('file_size')->comment('Kích thước tệp tin (tính bằng byte)');
            $table->string('file_name')->comment('Tên tệp tin');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng campaign_performance_logs - Lưu trữ thông tin hiệu suất chiến dịch theo ngày
        Schema::create('campaign_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->date('date')->comment('Ngày ghi nhận hiệu suất');
            $table->integer('impressions')->default(0)->comment('Số lần hiển thị');
            $table->integer('clicks')->default(0)->comment('Số lần nhấp');
            $table->integer('conversions')->default(0)->comment('Số lần chuyển đổi');
            $table->decimal('cost', 18, 8)->default(0)->comment('Chi phí đã sử dụng');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng tickets - Lưu trữ thông tin các vé hỗ trợ khách hàng
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Người tạo vé');
            $table->string('subject')->comment('Chủ đề');
            $table->text('description')->comment('Mô tả vấn đề');
            $table->smallInteger('status')->default(0)->comment('Trạng thái vé (trong enum TicketStatus)');
            $table->smallInteger('priority')->default(0)->comment('Mức độ ưu tiên (trong enum TicketPriority)');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->comment('Người được giao xử lý vé');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng ticket_conversations - Lưu trữ thông tin các cuộc trò chuyện trong vé hỗ trợ khách hàng
        Schema::create('ticket_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Người nhắn');
            $table->text('message')->comment('Nội dung tin nhắn');
            $table->string('attachment')->nullable()->comment('Đường dẫn tệp đính kèm (nếu có)');
            $table->smallInteger('reply_side')->comment('Bên trả lời (trong enum TicketReplySide)');
            $table->timestamps();
            $table->softDeletes();

        });

        // Bảng notifications - Lưu trữ thông tin các thông báo gửi đến người dùng
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->comment('Tiêu đề thông báo');
            $table->text('description')->comment('Nội dung thông báo');
            $table->text('data')->nullable()->comment('Dữ liệu bổ sung (json format)');
            $table->smallInteger('type')->comment('Loại thông báo (trong enum NotificationType)');
            $table->smallInteger('status')->default(0)->comment('Trạng thái thông báo (trong enum NotificationStatus)');
            $table->softDeletes();
            $table->timestamps();
        });

        // Bảng configs - Lưu trữ các cấu hình chung của hệ thống
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Khóa cấu hình');
            $table->smallInteger('type')->comment('Loại cấu hình (trong enum ConfigType)');
            $table->text('value')->comment('Giá trị cấu hình');
            $table->string('description')->nullable()->comment('Mô tả cấu hình');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('configs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ticket_conversations');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('campaign_performance_logs');
        Schema::dropIfExists('campaign_creative_files');
        Schema::dropIfExists('campaign_creatives');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('service_user_transaction_logs');
        Schema::dropIfExists('service_users');
        Schema::dropIfExists('service_package_fee_tiers');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('user_wallet_transaction_logs');
        Schema::dropIfExists('user_wallet_transactions');
        Schema::dropIfExists('user_wallets');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('user_otp');
        Schema::dropIfExists('user_referrals');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
