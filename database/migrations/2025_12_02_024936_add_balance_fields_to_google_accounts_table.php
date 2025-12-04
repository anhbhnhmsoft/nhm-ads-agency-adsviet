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
        Schema::table('google_accounts', function (Blueprint $table) {
            $table->decimal('balance', 18, 2)->nullable()->after('currency')->comment('Số dư tài khoản Google Ads');
            $table->boolean('balance_exhausted')->default(false)->after('balance')->comment('Đánh dấu tài khoản đã hết số dư');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            $table->dropColumn(['balance', 'balance_exhausted']);
        });
    }
};
