<?php

namespace App\Console\Commands;

use App\Models\MetaAdsCampaign;
use App\Models\ServiceUser;
use App\Models\User;
use App\Service\CurrencyExchangeService;
use Illuminate\Console\Command;

class DiagnoseOrder extends Command
{
    protected $signature = 'app:diagnose-order {query? : Order ID hoặc tên khách hàng}';

    protected $description = 'Kiểm tra toàn diện chi tiêu, ví, tài khoản ads và điều kiện thu phí/pause của đơn hàng';

    public function handle(CurrencyExchangeService $currencyService): int
    {
        $query = $this->argument('query') ?? '92448813401245160';

        $this->line('Đang tìm kiếm đơn hàng với từ khóa: ' . $query . ' ...');

        // Tìm theo ID đơn hàng nếu là số
        $serviceUser = is_numeric($query)
            ? ServiceUser::with(['package', 'user.wallet', 'metaAccount'])->find($query)
            : null;

        // Nếu không thấy, tìm theo tên hoặc username của user
        if (!$serviceUser) {
            $user = User::where('name', 'like', '%' . $query . '%')
                ->orWhere('username', 'like', '%' . $query . '%')
                ->first();

            if ($user) {
                $serviceUser = $user->serviceUsers()
                    ->with(['package', 'user.wallet', 'metaAccount'])
                    ->orderByDesc('id')
                    ->first();
            }
        }

        if (!$serviceUser) {
            $this->error('❌ Không tìm thấy đơn hàng hoặc khách hàng phù hợp với: ' . $query);
            return Command::FAILURE;
        }

        $this->line('=====================================================');
        $this->info('           BÁO CÁO CHẨN ĐOÁN ĐƠN HÀNG DỊCH VỤ        ');
        $this->line('=====================================================');

        // 1. Thông tin đơn hàng
        $walletBalance = (float) ($serviceUser->user?->wallet?->balance ?? 0);
        $this->comment('1. THÔNG TIN ĐƠN HÀNG:');
        $this->line('   - Order ID: ' . $serviceUser->id);
        $this->line('   - Khách hàng: ' . ($serviceUser->user?->name ?? 'N/A') . ' (@' . ($serviceUser->user?->username ?? 'N/A') . ')');
        $this->line('   - Số dư ví hiện tại: ' . number_format($walletBalance, 2) . ' USD');
        $this->line('   - Gói dịch vụ: ' . ($serviceUser->package?->name ?? 'N/A'));
        $this->line('   - Nguồn thanh toán: ' . ($serviceUser->package?->billing_source ?? 'N/A') . ' | Hình thức: ' . ($serviceUser->package?->payment_type ?? 'N/A'));
        $this->line('   - Trạng thái đơn: ' . $serviceUser->status . ' (' . $serviceUser->status_label . ')');

        // 2. Tài khoản ads liên kết
        $this->line('');
        $this->comment('2. TÀI KHOẢN ADS LIÊN KẾT (META ACCOUNTS):');
        $totalSpend = 0.0;
        $zeroDecimal = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
        $metaAccounts = $serviceUser->metaAccount;

        $this->line('   - Số lượng tài khoản gán: ' . $metaAccounts->count());
        foreach ($metaAccounts as $idx => $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $curr = strtoupper($a->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $converted = $currencyService->convert($amt, $curr, 'USD');
            $totalSpend += $converted;
            $this->line('   [' . ($idx + 1) . '] Act ID: ' . $a->account_id . ' | Tên: ' . $a->account_name . ' | Status: ' . $a->account_status . ' | BM ID: ' . $a->business_manager_id);
            $this->line('       -> Spend: ' . $a->amount_spent . ' ' . $curr . ' (~ ' . number_format($converted, 2) . ' USD)');
        }

        // 3. Tính toán chi tiêu & phí
        $this->line('');
        $this->comment('3. TÍNH TOÁN CHI TIÊU & PHÍ DỊCH VỤ:');
        $config = $serviceUser->config_account ?? [];
        $billedSpend = (float) ($config['spending_fee_billed_spend'] ?? 0);
        $unbilledSpend = max(0.0, $totalSpend - $billedSpend);
        $feePercent = (float) ($serviceUser->package?->spending_fee ?? 0);
        if ($feePercent <= 0 && ($serviceUser->package?->billing_source === 'customer_card')) {
            $feePercent = (float) ($serviceUser->package?->top_up_fee ?? 0);
        }
        $pendingFee = round($unbilledSpend * ($feePercent / 100), 2);

        $this->line('   - Tổng chi tiêu thực tế (Total Spend): ' . number_format($totalSpend, 2) . ' USD');
        $this->line('   - Chi tiêu ĐÃ thu phí (Billed Spend): ' . number_format($billedSpend, 2) . ' USD');
        $this->line('   - Chi tiêu CHƯA thu phí (Unbilled Spend): ' . number_format($unbilledSpend, 2) . ' USD');
        $this->line('   - Phí dịch vụ cần thu (Pending Fee): ' . number_format($pendingFee, 2) . ' USD (' . $feePercent . '%)');

        // 4. Danh sách campaign trong DB
        $this->line('');
        $this->comment('4. DANH SÁCH CHIẾN DỊCH TRONG DATABASE (meta_ads_campaigns):');
        $campaigns = MetaAdsCampaign::where('service_user_id', (string) $serviceUser->id)->get();
        $this->line('   - Tổng số campaign trong DB: ' . $campaigns->count());
        foreach ($campaigns as $c) {
            $this->line('     + ID: ' . $c->campaign_id . ' | Tên: ' . $c->name . ' | Status: ' . $c->status . ' | Effective: ' . $c->effective_status);
        }

        // 5. Kiểm tra điều kiện Cron job
        $this->line('');
        $this->comment('5. KIỂM TRA ĐIỀU KIỆN CHẠY CỦA CRON JOB BILL & PAUSE:');
        if ($unbilledSpend >= 100) {
            $this->info('   [A] Unbilled spend >= 100 USD? -> ✅ ĐẠT (' . number_format($unbilledSpend, 2) . ' USD -> Sẽ kích hoạt bill)');
        } else {
            $this->error('   [A] Unbilled spend >= 100 USD? -> ❌ CHƯA ĐẠT (' . number_format($unbilledSpend, 2) . ' USD < 100 USD -> Cron job tự động SKIP BỎ QUA)');
        }

        if ($walletBalance >= $pendingFee) {
            $this->info('   [B] Số dư ví đủ trả phí (' . number_format($pendingFee, 2) . ' USD)? -> ✅ ĐỦ TIỀN TRỪ PHÍ');
        } else {
            $this->error('   [B] Số dư ví đủ trả phí (' . number_format($pendingFee, 2) . ' USD)? -> ❌ THIẾU TIỀN TRỪ PHÍ');
        }

        if ($walletBalance < 100) {
            $this->warn('   [C] Số dư ví dưới ngưỡng an toàn (100 USD)? -> ⚠️ DƯỚI 100$ (' . number_format($walletBalance, 2) . ' USD -> Trạng thái Low Balance)');
        } else {
            $this->info('   [C] Số dư ví dưới ngưỡng an toàn (100 USD)? -> ✅ TRÊN 100$ (' . number_format($walletBalance, 2) . ' USD -> Trạng thái Healthy)');
        }

        // 6. Lịch sử thu phí và biến động Billed Spend
        $this->line('');
        $this->comment('6. LỊCH SỬ THU PHÍ (WALLET TRANSACTIONS) CỦA ĐƠN HÀNG:');
        $txs = \App\Models\UserWalletTransaction::where('reference_id', (string) $serviceUser->id)
            ->where('type', \App\Common\Constants\Wallet\WalletTransactionType::SPENDING_FEE->value)
            ->orderBy('id', 'asc')
            ->get();

        if ($txs->isEmpty()) {
            $this->line('   - Không tìm thấy giao dịch SPENDING_FEE nào có reference_id = ' . $serviceUser->id);
        } else {
            foreach ($txs as $idx => $tx) {
                $info = $tx->withdraw_info ?? [];
                $this->line('   [' . ($idx + 1) . '] TX #' . $tx->id . ' lúc ' . $tx->created_at);
                $this->line('       • Phí trừ ví: ' . $tx->amount . ' USD');
                $this->line('       • Spend tính phí: ' . ($info['spend_amount'] ?? 'N/A') . ' USD');
                $this->line('       • Billed Spend Trước: ' . ($info['billed_spend_before'] ?? 'N/A') . ' USD -> Sau: ' . ($info['billed_spend_after'] ?? 'N/A') . ' USD');
                $this->line('       • Nội dung: ' . $tx->description);
            }
        }

        // 7. Toàn bộ tài khoản của User trong hệ thống (cả gán và không gán)
        $this->line('');
        $this->comment('7. TOÀN BỘ TÀI KHOẢN CỦA USER / BM TRONG DATABASE:');
        $userId = $serviceUser->user_id;
        $allUserAccounts = \App\Models\MetaAccount::where('user_id', $userId)
            ->orWhere('service_user_id', (string) $serviceUser->id)
            ->get();

        $this->line('   - Tổng tài khoản tìm thấy: ' . $allUserAccounts->count());
        $sumAllUser = 0.0;
        foreach ($allUserAccounts as $acc) {
            $raw = (float) ($acc->amount_spent ?? 0);
            $curr = strtoupper($acc->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $converted = $currencyService->convert($amt, $curr, 'USD');
            $sumAllUser += $converted;
            $isCurrent = ((string) $acc->service_user_id === (string) $serviceUser->id) ? '✅ [ĐANG GẮN ĐƠN NÀY]' : '⚠️ [GẮN ĐƠN KHÁC HOẶC NULL: ' . ($acc->service_user_id ?? 'NULL') . ']';
            $this->line('     + ' . $acc->account_id . ' (' . $acc->name . '): ' . number_format($converted, 2) . ' USD ' . $isCurrent);
        }
        $this->line('   👉 TỔNG SPEND TOÀN BỘ TÀI KHOẢN CỦA USER: ' . number_format($sumAllUser, 2) . ' USD');

        $this->line('=====================================================');
        return Command::SUCCESS;
    }
}
