<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * # bảng meta_ads_account_insights
     * #note
     * - lưu trữ thông tin insight của tài khoản Meta Ads liên kết với người dùng sử dụng gói dịch vụ (chỉ có platform = Meta Ads)
     * # quan hệ
     * - n-1 với bảng service_users qua service_user_id
     * - n-1 với bảng meta_accounts qua meta_account_id
     * # cấu trúc
     * - id (int, primary key, auto-increment)
     * - service_user_id (int, foreign key to service_users.id, not null)
     * - meta_account_id (int, foreign key to meta_accounts.id, not null)
     * - date (date, index, not null) -- Ngày insight
     * - spend (varchar, nullable) -- Chi tiêu
     * - impressions (varchar, nullable) -- Lượt hiển thị
     * - reach (varchar, nullable) -- Lượt xem
     * - frequency (varchar, nullable) -- Tần suất hiển thị
     * - clicks (varchar, nullable) -- Lượt click
     * - inline_link_clicks (varchar, nullable) -- Lượt click vào liên kết trong nội dung
     * - ctr (varchar, nullable) -- Tỷ lệ click-through rate (CTR)
     * - cpc (varchar, nullable) -- Chi phí mỗi click
     * - cpm (varchar, nullable) -- Chi phí mỗi 1000 lượt hiển thị
     * - actions (json, nullable) -- Lượt hành động
     * - purchase_roas (varchar, nullable) -- Hiệu quả mua hàng
     * - last_sync_at (timestamp, nullable) -- Thời gian đồng bộ cuối cùng
     * - softDeletes
     * - timestamps
     */
    public function up(): void
    {
        Schema::create('meta_ads_account_insights', function (Blueprint $table) {
            $table->id();
            $table->comment('Lưu trữ thông tin insight của tài khoản Meta Ads liên kết với người dùng sử dụng gói dịch vụ (chỉ có platform = Meta Ads)');
            $table->foreignId('service_user_id')->constrained('service_users')->onDelete('cascade');
            $table->foreignId('meta_account_id')->constrained('meta_accounts')->onDelete('cascade');
            $table->date('date')->index()->comment('Ngày insight');
            $table->string('spend')->nullable()->comment('Chi tiêu');
            $table->string('impressions')->nullable()->comment('Lượt hiển thị');
            $table->string('reach')->nullable()->comment('Lượt xem');
            $table->string('frequency')->nullable()->comment('Tần suất hiển thị');
            $table->string('clicks')->nullable()->comment('Lượt click');
            $table->string('inline_link_clicks')->nullable()->comment('Lượt click vào liên kết trong nội dung');
            $table->string('ctr')->nullable()->comment('Tỷ lệ click-through rate (CTR)');
            $table->string('cpc')->nullable()->comment('Chi phí mỗi click');
            $table->string('cpm')->nullable()->comment('Chi phí mỗi 1000 lượt hiển thị');
            $table->json('actions')->nullable()->comment('Lượt hành động');
            $table->string('purchase_roas')->nullable()->comment('Hiệu quả mua hàng');
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
        Schema::dropIfExists('meta_ads_account_insights');
    }
};
