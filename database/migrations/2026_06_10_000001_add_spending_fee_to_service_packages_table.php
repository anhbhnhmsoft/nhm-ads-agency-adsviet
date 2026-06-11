<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('service_packages', 'spending_fee')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->decimal('spending_fee', 5, 2)
                ->default(0)
                ->after('top_up_fee')
                ->comment('Phí spending (%) tính theo chi tiêu quảng cáo thực tế');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('service_packages', 'spending_fee')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('spending_fee');
        });
    }
};
