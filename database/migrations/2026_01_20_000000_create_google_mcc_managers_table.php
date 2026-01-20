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
        Schema::create('google_mcc_managers', function (Blueprint $table) {
            $table->id();
            $table->string('mcc_id', 255)->index()->comment('Google Ads manager customer id (MCC)');
            $table->string('parent_mcc_id', 255)->nullable()->index()->comment('Parent MCC id (if this is a child manager)');
            $table->string('name', 255)->nullable()->comment('MCC (manager) descriptive name');
            $table->string('time_zone', 100)->nullable()->comment('Time zone of the manager');
            $table->string('currency', 10)->nullable()->comment('Currency code');
            $table->boolean('is_primary')->default(false)->comment('Is primary MCC from Platform Setting');
            $table->timestamp('last_synced_at')->nullable()->comment('Last synced at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_mcc_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_mcc_managers');
    }
};


