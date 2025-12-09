<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->timestamp('last_postpay_billed_at')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropColumn('last_postpay_billed_at');
        });
    }
};

