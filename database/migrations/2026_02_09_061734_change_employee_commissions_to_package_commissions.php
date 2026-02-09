<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('service_package_id')->nullable()->after('employee_id');
            
            $table->foreign('service_package_id')
                ->references('id')
                ->on('service_packages')
                ->onDelete('cascade');
            
            $table->index(['service_package_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropIndex(['service_package_id', 'type', 'is_active']);
            $table->dropColumn('service_package_id');
        });
    }
};
