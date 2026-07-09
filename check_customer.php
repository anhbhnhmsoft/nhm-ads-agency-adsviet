<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ServiceUser;
use App\Models\UserWalletTransaction;

$user = User::where('name', 'like', '%橙子%')->first();
if (!$user) {
    echo "NOT FOUND\n";
    exit;
}

echo "=== USER ===\n";
echo "ID: {$user->id}\n";
echo "Name: {$user->name}\n";

$wallet = $user->wallet;
if ($wallet) {
    echo "Wallet balance: {$wallet->balance} USD\n";
}

$serviceUsers = ServiceUser::where('user_id', $user->id)->with('package')->get();
echo "\n=== SERVICE USERS ({$serviceUsers->count()}) ===\n";
foreach ($serviceUsers as $su) {
    $config = $su->config_account ?? [];
    echo "SU:{$su->id} | status:{$su->status} | pkg:" . ($su->package?->name ?? 'N/A') . "\n";
    echo "  bm_id:" . ($config['bm_id'] ?? 'N/A') . " | mode:" . ($config['assign_mode'] ?? 'N/A') . "\n";
    echo "  topup:" . ($config['top_up_amount'] ?? 'N/A') . " | payment:" . ($config['payment_type'] ?? 'N/A') . "\n";
    echo "  account_ids:" . json_encode($config['account_ids'] ?? []) . "\n";
}

if ($wallet) {
    echo "\n=== WALLET TRANSACTIONS ===\n";
    $txns = UserWalletTransaction::where('wallet_id', $wallet->id)
        ->orderByDesc('created_at')
        ->limit(15)
        ->get();
    if ($txns->isEmpty()) {
        echo "(khong co giao dich)\n";
    }
    foreach ($txns as $tx) {
        echo "{$tx->created_at} | {$tx->type} | {$tx->amount} USD | {$tx->status} | " . ($tx->description ?? '') . "\n";
    }
}
