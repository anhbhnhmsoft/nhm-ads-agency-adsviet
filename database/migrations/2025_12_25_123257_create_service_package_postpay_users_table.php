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
        Schema::create('service_package_postpay_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')
                ->constrained('service_packages')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: một user chỉ được phép trả sau một lần cho một gói
            $table->unique(['service_package_id', 'user_id'], 'service_package_postpay_users_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_package_postpay_users');
    }
};
