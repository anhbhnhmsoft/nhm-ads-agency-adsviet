<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_accounts', 'payment_card')) {
                $table->string('payment_card')->nullable()->after('timezone_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('meta_accounts', 'payment_card')) {
                $table->dropColumn('payment_card');
            }
        });
    }
};
