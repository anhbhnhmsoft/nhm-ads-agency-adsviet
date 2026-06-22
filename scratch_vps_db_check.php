<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VPS DATABASE CHECK ===\n";
echo "Total meta_accounts count: " . DB::table('meta_accounts')->count() . "\n";

$bmId = '1310584400254306';
$accountsUnderBm = DB::table('meta_accounts')->where('business_manager_id', $bmId)->get();
echo "Accounts under BM {$bmId} count: " . $accountsUnderBm->count() . "\n";
foreach ($accountsUnderBm as $acc) {
    echo "  - {$acc->account_name} ({$acc->account_id})\n";
}

$accesses = DB::table('meta_account_business_manager_accesses')->where('source_bm_id', $bmId)->get();
echo "Accesses in access table for BM {$bmId} count: " . $accesses->count() . "\n";
foreach ($accesses as $acc) {
    echo "  - Account: {$acc->account_id} | owner_bm: {$acc->owner_bm_id}\n";
}

echo "Checking specific target accounts in meta_accounts:\n";
$targets = ['act_1601488500218022', 'act_380893730036367', 'act_630269054739398'];
foreach ($targets as $t) {
    $row = DB::table('meta_accounts')->where('account_id', $t)->first();
    if ($row) {
        echo "  - Found: {$t} | Name: {$row->account_name} | BM: {$row->business_manager_id}\n";
    } else {
        echo "  - NOT found: {$t}\n";
    }
}
echo "=== END VPS DATABASE CHECK ===\n";
