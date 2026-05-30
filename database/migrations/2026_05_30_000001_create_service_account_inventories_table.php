<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_account_inventories', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('service_package_id');
            $table->unsignedTinyInteger('platform');
            $table->string('account_id');
            $table->string('account_name')->nullable();
            $table->string('business_manager_id')->nullable();
            $table->string('customer_manager_id')->nullable();
            $table->string('source_account_type')->nullable();
            $table->unsignedBigInteger('source_account_id')->nullable();
            $table->string('status')->default('available');
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('assigned_service_user_id')->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->string('link_target_type')->nullable();
            $table->string('link_target_value')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('service_package_id')->references('id')->on('service_packages')->cascadeOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_service_user_id')->references('id')->on('service_users')->nullOnDelete();

            $table->unique(['service_package_id', 'platform', 'account_id'], 'svc_account_inventory_unique');
            $table->index(['service_package_id', 'platform', 'status'], 'svc_account_inventory_lookup');
            $table->index(['assigned_service_user_id'], 'svc_account_inventory_service_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_account_inventories');
    }
};
