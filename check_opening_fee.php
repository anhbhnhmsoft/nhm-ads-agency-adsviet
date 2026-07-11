<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceUser;
use Illuminate\Support\Facades\DB;

echo "=== Đơn hàng opening fee bị thiếu ===\n\n";

$sus = ServiceUser::with('package', 'user:id,name')
    ->where('status', 6) // ACTIVE
    ->get();

$affected = [];
foreach ($sus as $su) {
    $config = $su->config_account ?? [];
    $package = $su->package;
    if (!$package) continue;

    $openFee = (float) $package->open_fee;
    if ($openFee <= 0) continue;

    // Đếm số accounts trong config
    $accountsCount = 0;
    if (isset($config['accounts']) && is_array($config['accounts'])) {
        $accountsCount = count($config['accounts']);
    } elseif (!empty($config['account_id'])) {
        $accountsCount = 1;
    }

    if ($accountsCount <= 1) continue; // Chỉ check đơn > 1 TK

    $expectedFee = $openFee * $accountsCount;
    $actualFeeCharged = $openFee; // Luôn bị charge 1 TK

    // Check transaction đã charge bao nhiêu
    $chargedAmount = DB::table('user_wallet_transactions')
        ->join('user_wallets', 'user_wallets.id', '=', 'user_wallet_transactions.wallet_id')
        ->where('user_wallets.user_id', $su->user_id)
        ->where('user_wallet_transactions.reference_id', (string) $su->id)
        ->where('user_wallet_transactions.type', 6) // SERVICE_PURCHASE
        ->where('user_wallet_transactions.status', 4) // COMPLETED
        ->sum('user_wallet_transactions.amount');

    $chargedAmount = abs((float) $chargedAmount);

    $missingFee = $expectedFee - $chargedAmount;
    if ($missingFee > 0.01) {
        $affected[] = [
            'su_id' => $su->id,
            'user_name' => $su->user->name ?? 'N/A',
            'package' => $package->name,
            'open_fee' => $openFee,
            'accounts_count' => $accountsCount,
            'expected_fee' => $expectedFee,
            'charged' => $chargedAmount,
            'missing' => $missingFee,
        ];
    }
}

if (empty($affected)) {
    echo "Không có đơn nào bị thiếu phí.\n";
    exit;
}

echo "Tìm thấy " . count($affected) . " đơn bị thiếu phí:\n\n";

$totalMissing = 0;
foreach ($affected as $a) {
    echo "SU:{$a['su_id']} | {$a['user_name']} | {$a['package']}\n";
    echo "  TK: {$a['accounts_count']} × {$a['open_fee']} = {$a['expected_fee']} USD\n";
    echo "  Đã charge: {$a['charged']} USD\n";
    echo "  Thiếu: {$a['missing']} USD\n\n";
    $totalMissing += $a['missing'];
}

echo "=== TỔNG THIẾU: " . number_format($totalMissing, 2) . " USD ===\n";

// Tạo file SQL để charge
echo "\n=== SQL để charge thêm (chạy trên DB) ===\n";
foreach ($affected as $a) {
    echo "-- SU:{$a['su_id']} - {$a['user_name']} - thiếu {$a['missing']} USD\n";
}
