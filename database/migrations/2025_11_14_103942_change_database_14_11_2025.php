<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meta_accounts', function (Blueprint $table) {
            $table->comment('Bảng lưu trữ tài khoản Meta Ads');
            $table->id();
            $table->unsignedBigInteger('service_user_id')
                ->comment('ID người dùng dịch vụ');
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
            $table->string('account_id')->index()->comment('ID tài khoản Meta Ads');
            $table->string('account_name')->comment('Tên tài khoản Meta Ads');
            $table->smallInteger('account_status')->default(0)->comment('Trạng thái tài khoản (trong enum MetaAdsAccountStatus)');
            $table->string('spend_cap')->nullable()->comment('Giới hạn chi tiêu');
            $table->string('amount_spent')->nullable()->comment('Tổng số tiền đã chi tiêu');
            $table->string('balance')->nullable()->comment('Số dư tài khoản Meta Ads');
            $table->string('currency')->nullable()->comment('Tiền tệ tài khoản Meta Ads');
            $table->timestamp('created_time')->nullable()->comment('Thời gian tạo tài khoản');
            $table->boolean('is_prepay_account')->default(false)->comment('Là tài khoản trả trước hay không');
            $table->integer('timezone_id')->nullable()->comment('Mã múi giờ (VD: 1)');
            $table->string('timezone_name')->nullable()->comment('Mã múi giờ (VD: "America/Creston")');
            $table->timestamp('last_synced_at')->nullable()->comment('Thời gian đồng bộ cuối cùng');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('meta_ads_campaigns', function (Blueprint $table) {
            $table->comment('Bảng lưu trữ chiến dịch Meta Ads');
            $table->id();
            $table->unsignedBigInteger('service_user_id')
                ->comment('ID người dùng dịch vụ');
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
            $table->unsignedBigInteger('meta_account_id')
                ->comment('ID tài khoản Meta Ads');
            $table->foreign('meta_account_id')->references('id')->on('meta_accounts')->cascadeOnDelete();
            $table->string('campaign_id')->index()->comment('ID chiến dịch Meta Ads');
            $table->string('name')->comment('Tên chiến dịch Meta Ads');
            $table->string('status')->nullable()->comment('Trạng thái chiến dịch Meta Ads');
            $table->string('effective_status')->nullable()->comment('Trạng thái hiệu lực chiến dịch Meta Ads');
            $table->string('objective')->nullable()->comment('Mục tiêu chiến dịch Meta Ads');
            $table->string('daily_budget')->nullable()->comment('Ngân sách hàng ngày');
            $table->string('budget_remaining')->nullable()->comment('Ngân sách còn lại');
            $table->timestamp('created_time')->nullable()->comment('Thời gian tạo chiến dịch');
            $table->timestamp('start_time')->nullable()->comment('Thời gian bắt đầu chiến dịch');
            $table->timestamp('stop_time')->nullable()->comment('Thời gian kết thúc chiến dịch');
            $table->timestamp('last_synced_at')->nullable()->comment('Thời gian đồng bộ cuối cùng');
            $table->softDeletes();
            $table->timestamps();




        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_accounts');
        Schema::dropIfExists('meta_ads_campaigns');
    }
};
