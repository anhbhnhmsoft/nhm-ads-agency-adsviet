<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Xử lý bảng meta_ads_account_insights
        Schema::table('meta_ads_account_insights', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
        });
        Schema::table('meta_ads_account_insights', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable()->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->nullOnDelete();
        });

        // Xử lý bảng meta_ads_campaigns
        Schema::table('meta_ads_campaigns', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
        });
        Schema::table('meta_ads_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable()->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_ads_account_insights', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
            $table->unsignedBigInteger('service_user_id')->nullable(false)->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
        });

        Schema::table('meta_ads_campaigns', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
            $table->unsignedBigInteger('service_user_id')->nullable(false)->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
        });
    }
};
