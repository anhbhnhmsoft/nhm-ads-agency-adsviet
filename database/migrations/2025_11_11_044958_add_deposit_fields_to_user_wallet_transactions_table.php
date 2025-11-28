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
            $table->string('tx_hash')->nullable()->after('network')->comment('Hash giao dịch on-chain (nếu có)');
            $table->string('customer_name')->nullable()->after('tx_hash');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('deposit_address')->nullable()->after('customer_email')->comment('Địa chỉ ví nhận tiền');
            $table->string('payment_id')->nullable()->after('deposit_address')->comment('NowPayments payment ID');
            $table->string('pay_address')->nullable()->after('payment_id')->comment('Địa chỉ ví từ NowPayments để nhận thanh toán');
            $table->dateTime('expires_at')->nullable()->after('pay_address')->comment('Thời gian hết hạn lệnh nạp (15 phút sau khi tạo)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'tx_hash',
                'customer_name',
                'customer_email',
                'deposit_address',
                'payment_id',
                'pay_address',
                'expires_at',
            ]);
        });
    }
};
