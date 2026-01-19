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
        Schema::create('meta_business_managers', function (Blueprint $table) {
            $table->id();
            $table->string('bm_id', 255)->index()->comment('ID Business Manager');
            $table->string('parent_bm_id', 255)->nullable()->index()->comment('ID Business Manager cha (nếu là BM con)');
            $table->string('name', 255)->nullable()->comment('Tên Business Manager');
            $table->string('primary_page_id', 255)->nullable()->comment('ID trang chính');
            $table->string('primary_page_name', 255)->nullable()->comment('Tên trang chính');
            $table->string('verification_status', 50)->nullable()->comment('Trạng thái xác minh');
            $table->string('timezone_id', 50)->nullable()->comment('Múi giờ');
            $table->string('currency', 10)->nullable()->comment('Tiền tệ');
            $table->boolean('is_primary')->default(false)->comment('Có phải BM chính không');
            $table->timestamp('last_synced_at')->nullable()->comment('Thời gian đồng bộ cuối cùng');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_bm_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_business_managers');
    }
};
