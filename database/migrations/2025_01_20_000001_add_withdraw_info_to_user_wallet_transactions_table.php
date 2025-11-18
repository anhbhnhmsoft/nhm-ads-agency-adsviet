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
        Schema::table('user_wallet_transactions', function (Blueprint $table) {
            $table->json('withdraw_info')->nullable()->after('expires_at')->comment('Thông tin rút tiền: bank_name, account_holder, account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('withdraw_info');
        });
    }
};

