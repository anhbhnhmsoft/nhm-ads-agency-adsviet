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
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('platform_setting_id');
            $table->text('description')->nullable();
            $table->decimal('range_min_top_up', 18, 8)->default(0.00);
            $table->smallInteger('top_up_fee')->change()->default(0);
        });

        Schema::drop('service_package_fee_tiers');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('service_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('platform_setting_id')->nullable();
            $table->foreign('platform_setting_id')->references('id')->on('platform_settings');
            $table->dropColumn('description');
            $table->dropColumn('range_min_top_up');
            $table->smallInteger('top_up_fee')->change()->default(null);
        });
    }
};
