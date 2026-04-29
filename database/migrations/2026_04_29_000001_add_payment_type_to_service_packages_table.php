<?php

use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('service_packages', 'payment_type')) {
                $table->string('payment_type', 20)
                    ->default(ServicePackagePaymentType::PREPAY->value)
                    ->after('platform')
                    ->comment('Hinh thuc thanh toan cua goi: prepay hoac postpay');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            if (Schema::hasColumn('service_packages', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};

