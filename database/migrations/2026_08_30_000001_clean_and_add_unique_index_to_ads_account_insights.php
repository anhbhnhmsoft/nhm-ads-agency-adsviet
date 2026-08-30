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
        // 1. Dọn dẹp các bản ghi trùng lặp trong meta_ads_account_insights (chỉ giữ lại bản ghi có id lớn nhất)
        DB::statement('
            DELETE FROM meta_ads_account_insights a
            USING meta_ads_account_insights b
            WHERE a.id < b.id
              AND a.meta_account_id = b.meta_account_id
              AND a.date = b.date
              AND a.deleted_at IS NULL
              AND b.deleted_at IS NULL;
        ');

        // 2. Dọn dẹp các bản ghi trùng lặp trong google_ads_account_insights (chỉ giữ lại bản ghi có id lớn nhất)
        DB::statement('
            DELETE FROM google_ads_account_insights a
            USING google_ads_account_insights b
            WHERE a.id < b.id
              AND a.google_account_id = b.google_account_id
              AND a.date = b.date
              AND a.deleted_at IS NULL
              AND b.deleted_at IS NULL;
        ');

        // 3. Tạo Unique Index cho meta_ads_account_insights (hỗ trợ SoftDeletes qua partial index)
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS meta_ads_account_insights_account_date_unique 
            ON meta_ads_account_insights (meta_account_id, date) 
            WHERE deleted_at IS NULL;
        ');

        // 4. Tạo Unique Index cho google_ads_account_insights (hỗ trợ SoftDeletes qua partial index)
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS google_ads_account_insights_account_date_unique 
            ON google_ads_account_insights (google_account_id, date) 
            WHERE deleted_at IS NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS meta_ads_account_insights_account_date_unique;');
        DB::statement('DROP INDEX IF EXISTS google_ads_account_insights_account_date_unique;');
    }
};
