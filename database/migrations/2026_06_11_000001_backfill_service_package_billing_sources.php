<?php

use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('service_packages', 'billing_source')) {
            return;
        }

        DB::table('service_packages')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%your card%'])
                    ->orWhere('payment_type', ServicePackagePaymentType::POSTPAY->value);
            })
            ->update(['billing_source' => AccountBillingSource::CUSTOMER_CARD->value]);

        DB::table('service_packages')
            ->whereRaw('LOWER(name) LIKE ?', ['%credit line%'])
            ->update(['billing_source' => AccountBillingSource::SUPPLIER_CREDIT_LINE->value]);

        DB::table('service_packages')
            ->whereRaw('LOWER(name) LIKE ?', ['%our card%'])
            ->update(['billing_source' => AccountBillingSource::ADVIET_CARD->value]);
    }

    public function down(): void
    {
        //
    }
};
