<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_accounts', 'disable_reason')) {
                $table->string('disable_reason')->nullable()->after('account_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('meta_accounts', 'disable_reason')) {
                $table->dropColumn('disable_reason');
            }
        });
    }
};

