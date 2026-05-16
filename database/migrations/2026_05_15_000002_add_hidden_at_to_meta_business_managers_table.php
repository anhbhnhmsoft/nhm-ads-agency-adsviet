<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_business_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_business_managers', 'hidden_at')) {
                $table->timestamp('hidden_at')->nullable()->after('access_source')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_business_managers', function (Blueprint $table) {
            if (Schema::hasColumn('meta_business_managers', 'hidden_at')) {
                $table->dropColumn('hidden_at');
            }
        });
    }
};
