<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('google_ads_campaigns', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
        });

        Schema::table('google_ads_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable()->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('google_ads_campaigns', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
        });

        Schema::table('google_ads_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable(false)->change();
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
        });
    }
};
