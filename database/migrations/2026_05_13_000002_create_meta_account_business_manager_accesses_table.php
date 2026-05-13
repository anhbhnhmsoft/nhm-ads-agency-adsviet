<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_account_business_manager_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('source_bm_id')->index();
            $table->string('owner_bm_id')->nullable()->index();
            $table->string('account_id')->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source_bm_id', 'account_id'], 'meta_account_bm_access_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_account_business_manager_accesses');
    }
};
