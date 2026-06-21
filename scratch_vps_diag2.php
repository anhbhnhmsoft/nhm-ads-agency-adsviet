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

$reflector = new ReflectionClass(MetaService::class);

echo "=== DETAIL SYNC DIAGNOSTIC ===\n";

// 1. syncSelfBusinessManagers
$syncSelfBMs = $reflector->getMethod('syncSelfBusinessManagers');
$syncSelfBMs->setAccessible(true);
try {
    $selfBMs = $syncSelfBMs->invoke($metaService, $bmId);
    echo "1. syncSelfBusinessManagers count: " . count($selfBMs) . "\n";
} catch (\Throwable $e) {
    echo "1. [ERROR] syncSelfBusinessManagers failed: " . $e->getMessage() . "\n";
}

// 2. syncMetaAccountsFromManagerEdge - owner
$syncMetaAccountsFromManagerEdge = $reflector->getMethod('syncMetaAccountsFromManagerEdge');
$syncMetaAccountsFromManagerEdge->setAccessible(true);
try {
    $ownerAccounts = $syncMetaAccountsFromManagerEdge->invoke($metaService, $bmId, 'owner');
    echo "2. syncMetaAccountsFromManagerEdge(owner) returned: " . ($ownerAccounts === null ? 'null' : count($ownerAccounts) . " accounts") . "\n";
} catch (\Throwable $e) {
    echo "2. [ERROR] syncMetaAccountsFromManagerEdge(owner) failed: " . $e->getMessage() . "\n";
}

// 3. syncMetaAccountsFromManagerEdge - client
try {
    $clientAccounts = $syncMetaAccountsFromManagerEdge->invoke($metaService, $bmId, 'client');
    echo "3. syncMetaAccountsFromManagerEdge(client) returned: " . ($clientAccounts === null ? 'null' : count($clientAccounts) . " accounts") . "\n";
} catch (\Throwable $e) {
    echo "3. [ERROR] syncMetaAccountsFromManagerEdge(client) failed: " . $e->getMessage() . "\n";
}

echo "=== END DETAIL SYNC DIAGNOSTIC ===\n";
