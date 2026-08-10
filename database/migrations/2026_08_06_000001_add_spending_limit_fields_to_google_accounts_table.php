<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('google_accounts', 'spending_limit')) {
                $table->decimal('spending_limit', 18, 2)->nullable()->after('amount_spent')->comment('Giới hạn chi tiêu (approved_spending_limit) của tài khoản Google Ads');
            }
            if (!Schema::hasColumn('google_accounts', 'total_spent')) {
                $table->decimal('total_spent', 18, 2)->nullable()->after('spending_limit')->comment('Tổng chi tiêu đã tính vào account budget (amount_served)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('google_accounts', function (Blueprint $table) {
            foreach (['spending_limit', 'total_spent'] as $column) {
                if (Schema::hasColumn('google_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
