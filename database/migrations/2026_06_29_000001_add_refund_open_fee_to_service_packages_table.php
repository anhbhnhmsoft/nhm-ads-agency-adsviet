<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->boolean('refund_open_fee')->default(false)->after('cashback_percent');
            $table->decimal('min_spend_for_refund', 18, 2)->nullable()->after('refund_open_fee');
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn(['refund_open_fee', 'min_spend_for_refund']);
        });
    }
};
