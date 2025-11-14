<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thay đổi kiểu dữ liệu của config_account
        DB::statement('
    ALTER TABLE service_users
    ALTER COLUMN config_account TYPE json USING config_account::json
');
        DB::statement('
    ALTER TABLE service_users
    ALTER COLUMN config_account DROP DEFAULT,
    ALTER COLUMN config_account SET NOT NULL
');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
            ALTER TABLE service_users
            ALTER COLUMN config_account
            TYPE text
            USING config_account::text
        ');
    }
};
