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
        Schema::table('user_devices', function (Blueprint $table) {
            $table->string('device_name')->nullable()
                ->comment('Tên thiết bị');
            $table->string('notification_token')->nullable()
                ->comment('Token thông báo');
            $table->dropUnique(['device_id']);
            $table->index('device_id');
            $table->string('ip')->nullable()->comment('IP Address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn(['ip','device_name','notification_token']);
            $table->dropIndex(['device_id']);
            $table->unique('device_id');
        });
    }
};
