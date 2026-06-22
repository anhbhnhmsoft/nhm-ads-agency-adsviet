<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Service\MetaService;
use App\Service\MetaBusinessService;
use Illuminate\Support\Facades\DB;

$metaService = app(MetaService::class);
$metaBusinessService = app(MetaBusinessService::class);
$bmId = '1310584400254306';

$setting = DB::table('platform_settings')->where('platform', 2)->whereNull('deleted_at')->first();
$settingId = $setting ? (string) $setting->id : null;

if ($settingId) {
    $metaBusinessService->setSettingId($settingId);
}
$metaBusinessService->resetApi();

echo "=== DIAGNOSTIC 3 ===\n";

// Get hidden BMs
$hiddenBmIds = DB::table('meta_business_managers')
    ->whereNotNull('hidden_at')
    ->pluck('bm_id')
    ->map('strval')
    ->toArray();
echo "Hidden BM IDs: " . json_encode($hiddenBmIds) . "\n";

$result = $metaBusinessService->getClientAdsAccountPaginated($bmId, 100);
if ($result->isError()) {
    echo "Error fetching client accounts: " . $result->getMessage() . "\n";
    exit;
}

$data = $result->getData();
$accounts = $data['data'] ?? [];

echo "Fetched " . count($accounts) . " client accounts.\n";

$targetIds = ['act_1601488500218022', 'act_380893730036367', 'act_630269054739398'];

foreach ($accounts as $acc) {
    $accId = $acc['id'] ?? null;
    $isTarget = in_array($accId, $targetIds, true);
    
    $business = $acc['business'] ?? null;
    $ownerBmId = $business['id'] ?? $bmId;
    $ownerBmName = $business['name'] ?? null;
    $isHidden = in_array((string)$ownerBmId, $hiddenBmIds, true);
    
    if ($isTarget) {
        echo "TARGET account detail:\n";
        echo "  ID: {$accId}\n";
        echo "  Name: " . ($acc['name'] ?? 'N/A') . "\n";
        echo "  Business ID: " . ($ownerBmId ?: 'null') . " (" . ($ownerBmName ?: 'N/A') . ")\n";
        echo "  Is Owner BM Hidden: " . ($isHidden ? 'YES' : 'NO') . "\n";
    }
}

// Let's run processAccountListData manually and see if it throws any exception
echo "\nRunning processAccountListData manually on targets:\n";
$targetsToProcess = array_filter($accounts, function($acc) use ($targetIds) {
    return in_array($acc['id'] ?? null, $targetIds, true);
});

try {
    // We can use reflection to call processAccountListData
    $reflector = new ReflectionClass(MetaService::class);
    $method = $reflector->getMethod('processAccountListData');
    $method->setAccessible(true);
    
    $res = $method->invoke($metaService, array_values($targetsToProcess), $bmId);
    echo "Processed accounts, returned: " . json_encode($res) . "\n";
} catch (\Throwable $e) {
    echo "[EXCEPTION] processAccountListData failed: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

echo "=== END DIAGNOSTIC 3 ===\n";
