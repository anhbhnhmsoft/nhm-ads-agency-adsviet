<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_accounts', 'business_manager_id')) {
                $table->string('business_manager_id')->nullable()->after('service_user_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('meta_accounts', 'business_manager_id')) {
                $table->dropColumn('business_manager_id');
            }
        });
    }
};

