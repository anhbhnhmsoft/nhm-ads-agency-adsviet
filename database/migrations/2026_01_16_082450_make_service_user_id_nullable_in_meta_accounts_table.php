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
        Schema::table('meta_accounts', function (Blueprint $table) {
            $table->dropForeign(['service_user_id']);
        });

        Schema::table('meta_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('service_user_id')->nullable(false)->change();
        });

        Schema::table('meta_accounts', function (Blueprint $table) {
            $table->foreign('service_user_id')->references('id')->on('service_users')->cascadeOnDelete();
        });
    }
};
