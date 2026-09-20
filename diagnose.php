<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = $argv[1] ?? '92448813401245160';
$su = App\Models\ServiceUser::with(['package', 'user.wallet', 'metaAccount'])->find($id);
if (!$su) {
    $u = App\Models\User::where('name', 'like', '%' . $id . '%')->orWhere('username', 'like', '%' . $id . '%')->first();
    if ($u) $su = $u->serviceUsers()->with(['package', 'user.wallet', 'metaAccount'])->orderByDesc('id')->first();
}
if (!$su) { echo "❌ Không tìm thấy đơn hàng hoặc khách hàng: $id\n"; exit(1); }

echo "=====================================================\n";
echo "           BÁO CÁO CHẨN ĐOÁN ĐƠN HÀNG DỊCH VỤ        \n";
echo "=====================================================\n";
echo "1. THÔNG TIN ĐƠN HÀNG:\n";
echo "   - Order ID: {$su->id}\n";
echo "   - Khách hàng: " . ($su->user?->name ?? 'N/A') . " (@" . ($su->user?->username ?? 'N/A') . ")\n";
echo "   - Số dư ví: " . number_format((float)($su->user?->wallet?->balance ?? 0), 2) . " USD\n";
echo "   - Gói: " . ($su->package?->name ?? 'N/A') . " | Nguồn: " . ($su->package?->billing_source ?? 'N/A') . " | Loại: " . ($su->package?->payment_type ?? 'N/A') . "\n";
echo "   - Trạng thái đơn: {$su->status} ({$su->status_label})\n\n";

echo "2. TÀI KHOẢN ADS LIÊN KẾT (META ACCOUNTS):\n";
$totalSpend = 0.0;
$cs = app(App\Service\CurrencyExchangeService::class);
$zero = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
foreach ($su->metaAccount as $idx => $a) {
    $raw = (float)($a->amount_spent ?? 0);
    $curr = strtoupper($a->currency ?? 'USD');
    $amt = in_array($curr, $zero) ? $raw : $raw / 100;
    $usd = $cs->convert($amt, $curr, 'USD');
    $totalSpend += $usd;
    echo "   [" . ($idx + 1) . "] Act ID: {$a->account_id} | Tên: {$a->account_name} | Status: {$a->account_status} | BM ID: {$a->business_manager_id}\n";
    echo "       -> Spend: {$a->amount_spent} {$curr} (~ " . number_format($usd, 2) . " USD)\n";
}

echo "\n3. TÍNH TOÁN CHI TIÊU & PHÍ DỊCH VỤ:\n";
$cfg = $su->config_account ?? [];
$billedSpend = (float)($cfg['spending_fee_billed_spend'] ?? 0);
$unbilledSpend = max(0.0, $totalSpend - $billedSpend);
$feePercent = (float)($su->package?->spending_fee ?? 0);
if ($feePercent <= 0 && ($su->package?->billing_source === 'customer_card')) {
    $feePercent = (float)($su->package?->top_up_fee ?? 0);
}
$pendingFee = round($unbilledSpend * ($feePercent / 100), 2);
echo "   - Tổng chi tiêu thực tế (Total Spend): " . number_format($totalSpend, 2) . " USD\n";
echo "   - Chi tiêu ĐÃ thu phí (Billed Spend): " . number_format($billedSpend, 2) . " USD\n";
echo "   - Chi tiêu CHƯA thu phí (Unbilled Spend): " . number_format($unbilledSpend, 2) . " USD\n";
echo "   - Phí dịch vụ cần thu (Pending Fee): " . number_format($pendingFee, 2) . " USD ({$feePercent}%)\n\n";

echo "4. DANH SÁCH CAMPAIGN TRONG DATABASE:\n";
$camps = App\Models\MetaAdsCampaign::where('service_user_id', (string)$su->id)->get();
echo "   - Tổng số campaign trong DB: " . $camps->count() . "\n";
foreach ($camps as $c) {
    echo "     + ID: {$c->campaign_id} | Tên: {$c->name} | Status: {$c->status} | Effective: {$c->effective_status}\n";
}

echo "\n5. KIỂM TRA ĐIỀU KIỆN CRON JOB BILL & PAUSE:\n";
$bal = (float)($su->user?->wallet?->balance ?? 0);
echo "   [A] Unbilled spend >= 100 USD? -> " . ($unbilledSpend >= 100 ? "✅ ĐẠT ($unbilledSpend USD)" : "❌ CHƯA ĐẠT ($unbilledSpend USD < 100 USD -> Cron job SKIP)") . "\n";
echo "   [B] Số dư ví đủ trả phí ($pendingFee USD)? -> " . ($bal >= $pendingFee ? "✅ ĐỦ TIỀN TRỪ PHÍ" : "❌ THIẾU TIỀN TRỪ PHÍ") . "\n";
echo "   [C] Số dư ví dưới ngưỡng 100 USD? -> " . ($bal < 100 ? "⚠️ DƯỚI 100$ ($bal USD -> Trạng thái Low Balance)" : "✅ TRÊN 100$ ($bal USD)") . "\n";
echo "=====================================================\n";
