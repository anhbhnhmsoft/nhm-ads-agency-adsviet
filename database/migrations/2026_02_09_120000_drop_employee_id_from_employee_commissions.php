<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_commissions', 'employee_id')) {
                $table->dropColumn('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_commissions', 'employee_id')) {
                $table->string('employee_id')->nullable();
            }
        });
    }
};


