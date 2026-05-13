<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meta_account_business_manager_accesses')) {
            return;
        }

        DB::table('meta_accounts')
            ->whereNotNull('business_manager_id')
            ->orderBy('id')
            ->chunk(500, function ($accounts) {
                $bmIds = $accounts
                    ->pluck('business_manager_id')
                    ->filter()
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->values()
                    ->toArray();

                $parentMap = DB::table('meta_business_managers')
                    ->whereIn('bm_id', $bmIds)
                    ->pluck('parent_bm_id', 'bm_id')
                    ->toArray();

                $now = now();
                $rows = [];

                foreach ($accounts as $account) {
                    $ownerBmId = (string) $account->business_manager_id;
                    $parentBmId = $parentMap[$ownerBmId] ?? null;
                    $sourceBmId = $parentBmId ? (string) $parentBmId : $ownerBmId;

                    $rows[] = [
                        'source_bm_id' => $sourceBmId,
                        'owner_bm_id' => $ownerBmId,
                        'account_id' => (string) $account->account_id,
                        'last_synced_at' => $account->last_synced_at ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('meta_account_business_manager_accesses')->upsert(
                        $rows,
                        ['source_bm_id', 'account_id'],
                        ['owner_bm_id', 'last_synced_at', 'updated_at']
                    );
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('meta_account_business_manager_accesses')) {
            DB::table('meta_account_business_manager_accesses')->truncate();
        }
    }
};
