<?php

use App\Common\Constants\MetaBusinessManager\MetaBusinessManagerSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_business_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_business_managers', 'is_direct_access')) {
                $table->boolean('is_direct_access')->default(false)->after('is_primary')->index();
            }

            if (!Schema::hasColumn('meta_business_managers', 'access_source')) {
                $table->string('access_source', 50)->default(MetaBusinessManagerSource::RELATED)->after('is_direct_access')->index();
            }
        });

        DB::table('meta_business_managers')
            ->whereNull('parent_bm_id')
            ->update([
                'is_direct_access' => true,
                'access_source' => MetaBusinessManagerSource::SELF,
            ]);

        DB::table('meta_business_managers')
            ->whereNotNull('parent_bm_id')
            ->where(function ($query) {
                $query->whereNull('access_source')
                    ->orWhere('access_source', '');
            })
            ->update([
                'is_direct_access' => false,
                'access_source' => MetaBusinessManagerSource::RELATED,
            ]);
    }

    public function down(): void
    {
        Schema::table('meta_business_managers', function (Blueprint $table) {
            if (Schema::hasColumn('meta_business_managers', 'access_source')) {
                $table->dropColumn('access_source');
            }

            if (Schema::hasColumn('meta_business_managers', 'is_direct_access')) {
                $table->dropColumn('is_direct_access');
            }
        });
    }
};
