<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceUser;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use Illuminate\Support\Facades\DB;

$suId = '87566299652162751';
$missingAmount = 20.00;

$su = ServiceUser::with('package', 'user:id,name')->find($suId);
if (!$su) { echo "Service user not found\n"; exit; }

$wallet = $su->user->wallet;
if (!$wallet) { echo "Wallet not found\n"; exit; }

echo "User: {$su->user->name}\n";
echo "Wallet balance: {$wallet->balance} USD\n";
echo "Missing fee: {$missingAmount} USD\n\n";

if ((float) $wallet->balance < $missingAmount) {
    echo "INSUFFICIENT BALANCE! Need {$missingAmount}, have {$wallet->balance}\n";
    echo "Nạp thêm tiền vào ví trước khi charge.\n";
    exit;
}

// Charge
DB::transaction(function () use ($wallet, $su, $missingAmount, $suId) {
    $newBalance = (float) $wallet->balance - $missingAmount;
    $wallet->update(['balance' => $newBalance]);

    $tx = \App\Models\UserWalletTransaction::create([
        'wallet_id' => $wallet->id,
        'amount' => -$missingAmount,
        'type' => WalletTransactionType::SERVICE_PURCHASE->value,
        'status' => WalletTransactionStatus::COMPLETED->value,
        'description' => "Bổ sung phí mở tài khoản (thiếu do bug: 2 TK × $20 - đã charge $20)",
        'reference_id' => $suId,
    ]);

    echo "=== CHARGE SUCCESS ===\n";
    echo "Charged: {$missingAmount} USD\n";
    echo "New balance: {$newBalance} USD\n";
    echo "Transaction ID: {$tx->id}\n";
});
