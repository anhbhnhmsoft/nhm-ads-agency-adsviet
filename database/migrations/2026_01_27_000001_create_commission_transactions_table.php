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
        Schema::create('commission_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employee_id');
            $table->string('customer_id')->nullable();
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('base_amount', 15, 2);
            $table->decimal('commission_rate', 8, 4);
            $table->decimal('commission_amount', 15, 2);
            $table->string('period')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index(['employee_id', 'type', 'is_paid']);
            $table->index(['employee_id', 'period']);
            $table->index(['customer_id', 'type']);
            $table->index('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_transactions');
    }
};


