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
        Schema::create('meta_business_asset_groups', function (Blueprint $table) {
            $table->id(); // Use default BigInt ID
            $table->string('group_id')->unique(); // Meta Asset Group ID
            $table->string('name');
            $table->string('business_manager_id')->nullable()->index(); // Meta BM ID (String)
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('meta_account_asset_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meta_account_id');
            $table->unsignedBigInteger('meta_business_asset_group_id');
            $table->timestamps();
            
            $table->unique(['meta_account_id', 'meta_business_asset_group_id'], 'meta_acc_asset_group_unique');
            
            $table->foreign('meta_account_id')->references('id')->on('meta_accounts')->onDelete('cascade');
            $table->foreign('meta_business_asset_group_id')->references('id')->on('meta_business_asset_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_account_asset_group');
        Schema::dropIfExists('meta_business_asset_groups');
    }
};
