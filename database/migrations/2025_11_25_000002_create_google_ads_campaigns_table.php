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
        Schema::create('google_ads_campaigns', function (Blueprint $table) {
            $table->comment('Bảng lưu trữ chiến dịch Google Ads của service user');
            $table->id();
            $table->foreignId('service_user_id')
                ->constrained('service_users')
                ->onDelete('cascade');
            $table->foreignId('google_account_id')
                ->constrained('google_accounts')
                ->onDelete('cascade');
            $table->string('campaign_id')->index()->comment('ID chiến dịch Google Ads');
            $table->string('name')->comment('Tên chiến dịch');
            $table->string('status')->nullable()->comment('Trạng thái chiến dịch');
            $table->string('effective_status')->nullable()->comment('Trạng thái hiệu lực');
            $table->string('objective')->nullable()->comment('Mục tiêu chiến dịch');
            $table->string('daily_budget')->nullable()->comment('Ngân sách/ngày');
            $table->string('budget_remaining')->nullable()->comment('Ngân sách còn lại');
            $table->timestamp('start_time')->nullable()->comment('Thời gian bắt đầu');
            $table->timestamp('stop_time')->nullable()->comment('Thời gian kết thúc');
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
        Schema::dropIfExists('google_ads_campaigns');
    }
};

