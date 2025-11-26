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
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->comment('Bảng lưu trữ tài khoản Google Ads');
            $table->foreignId('service_user_id')->constrained('service_users')->onDelete('cascade');
            $table->string('account_id')->index()->comment('ID tài khoản Google Ads');
            $table->string('account_name')->comment('Tên tài khoản Google Ads');
            $table->smallInteger('account_status')->default(0)->comment('Trạng thái tài khoản');
            $table->string('currency')->nullable()->comment('Loại tiền tệ');
            $table->string('customer_manager_id')->nullable()->comment('MCC quản lý');
            $table->string('time_zone')->nullable()->comment('Múi giờ tài khoản');
            $table->string('primary_email')->nullable()->comment('Email chính');
            $table->timestamp('last_synced_at')->nullable()->comment('Thời gian đồng bộ cuối');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('google_ads_account_insights', function (Blueprint $table) {
            $table->id();
            $table->comment('Lưu trữ thông tin insight của tài khoản Google Ads liên kết với người dùng dịch vụ');
            $table->foreignId('service_user_id')->constrained('service_users')->onDelete('cascade');
            $table->foreignId('google_account_id')->constrained('google_accounts')->onDelete('cascade');
            $table->date('date')->index()->comment('Ngày insight');
            $table->string('spend')->nullable()->comment('Chi tiêu');
            $table->string('impressions')->nullable()->comment('Lượt hiển thị');
            $table->string('clicks')->nullable()->comment('Lượt click');
            $table->string('conversions')->nullable()->comment('Số chuyển đổi');
            $table->string('ctr')->nullable()->comment('CTR');
            $table->string('cpc')->nullable()->comment('CPC');
            $table->string('cpm')->nullable()->comment('CPM');
            $table->json('conversion_actions')->nullable()->comment('Chi tiết chuyển đổi (JSON)');
            $table->string('roas')->nullable()->comment('ROAS');
            $table->timestamp('last_synced_at')->nullable()->comment('Thời gian đồng bộ cuối');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_ads_account_insights');
        Schema::dropIfExists('google_accounts');
    }
};

