<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('google_accounts', 'amount_spent')) {
                $table->string('amount_spent')->nullable()->after('balance_exhausted')->comment('Chi tiêu hôm nay của tài khoản Google Ads');
            }
        });
    }

    public function down(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('google_accounts', 'amount_spent')) {
                $table->dropColumn('amount_spent');
            }
        });
    }
};
