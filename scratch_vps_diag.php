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

echo "=== DIAGNOSTIC REPORT ===\n";
echo "1. Active Setting ID: " . ($settingId ?: 'NONE') . "\n";
if ($setting) {
    $config = is_string($setting->config) ? json_decode($setting->config, true) : $setting->config;
    echo "   App ID: " . ($config['app_id'] ?? 'NONE') . "\n";
    echo "   Token Length: " . (isset($config['access_token']) ? strlen($config['access_token']) : 0) . " chars\n";
}

if ($settingId) {
    $metaBusinessService->setSettingId($settingId);
}
$metaBusinessService->resetApi();

echo "2. Fetching BM Info from Meta...\n";
$parentInfo = $metaBusinessService->getBusinessById($bmId);
if ($parentInfo->isError()) {
    echo "   [ERROR] Failed to fetch BM info: " . $parentInfo->getMessage() . "\n";
} else {
    echo "   [SUCCESS] BM Name: " . ($parentInfo->getData()['name'] ?? 'N/A') . "\n";
}

echo "3. Fetching Client Accounts directly from Meta...\n";
$clientResult = $metaBusinessService->getClientAdsAccountPaginated($bmId, 10);
if ($clientResult->isError()) {
    echo "   [ERROR] Failed to fetch client accounts: " . $clientResult->getMessage() . "\n";
} else {
    $data = $clientResult->getData();
    echo "   [SUCCESS] Found " . count($data['data'] ?? []) . " client accounts in first page.\n";
    foreach ($data['data'] ?? [] as $acc) {
        echo "     - {$acc['name']} ({$acc['id']})\n";
    }
}

echo "4. Running basic sync method directly...\n";
$resBasic = $metaService->syncFromBusinessManagerIdBasic($bmId, $settingId);
echo "   Result: " . ($resBasic->isSuccess() ? "SUCCESS" : "ERROR: " . $resBasic->getMessage()) . "\n";

echo "=== END REPORT ===\n";
