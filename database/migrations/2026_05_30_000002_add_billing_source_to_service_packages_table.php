<?php

use App\Common\Constants\ServicePackage\AccountBillingSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('service_packages', 'billing_source')) {
                $table->string('billing_source')
                    ->default(AccountBillingSource::ADVIET_CARD->value)
                    ->after('payment_type')
                    ->comment('Nguồn billing: customer_card, adviet_card, supplier_credit_line');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            if (Schema::hasColumn('service_packages', 'billing_source')) {
                $table->dropColumn('billing_source');
            }
        });
    }
};
