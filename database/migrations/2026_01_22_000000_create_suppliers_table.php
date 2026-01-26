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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->notNull()->comment('Tên nhà cung cấp dịch vụ');
            $table->decimal('open_fee', 18, 8)->default(0)->comment('Chi phí mở tài khoản (trả trước)');
            $table->decimal('postpay_fee', 18, 8)->default(0)->comment('Chi phí nhà cung cấp (dành cho trả sau)');
            $table->text('monthly_spending_fee_structure')->nullable()->comment('Monthly Spending & Fee Structure (JSON format)');
            $table->boolean('disabled')->default(false)->comment('Trạng thái vô hiệu hóa');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

