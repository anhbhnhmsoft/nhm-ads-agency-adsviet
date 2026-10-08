<?php

namespace App\Console\Commands;

use App\Common\Constants\Platform\PlatformType;
use App\Core\Logging;
use App\Models\MetaAccount;
use App\Models\MetaAccountBusinessManagerAccess;
use App\Models\MetaBusinessManager;
use App\Service\MetaBusinessService;
use App\Service\MetaService;
use Illuminate\Console\Command;

class CleanupRemovedMetaAccounts extends Command
{
    protected $signature = 'app:cleanup-removed-meta-accounts {--bm= : Business Manager ID cụ thể cần quét} {--dry-run : Chỉ kiểm tra danh sách mà không thực hiện xóa}';

    protected $description = 'Quét và dọn dẹp các tài khoản quảng cáo Meta đã bị xóa khỏi Business Manager (xem như bỏ luôn tài khoản)';

    public function __construct(
        protected MetaService $metaService,
        protected MetaBusinessService $metaBusinessService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $specificBmId = $this->option('bm');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('====================================================================');
        $this->info('  BẮT ĐẦU QUÉT VÀ DỌN DẸP TÀI KHOẢN META BỊ XÓA KHỎI BM');
        if ($isDryRun) {
            $this->warn('  [CHẾ ĐỘ DRY-RUN]: Chỉ kiểm tra và hiển thị, không thay đổi dữ liệu.');
        }
        $this->info('====================================================================');

        $bmQuery = MetaBusinessManager::query()->whereNull('hidden_at');
        if ($specificBmId) {
            $bmQuery->where('bm_id', $specificBmId);
        }

        $bms = $bmQuery->get();
        if ($bms->isEmpty()) {
            $this->warn('Không tìm thấy Business Manager nào phù hợp.');
            return Command::SUCCESS;
        }

        $totalCheckedBms = 0;
        $totalStaleFound = 0;
        $totalDiscarded = 0;

        foreach ($bms as $bm) {
            $bmId = (string) $bm->bm_id;
            $bmName = $bm->name ?: $bmId;
            $this->line("\n🔍 Đang kiểm tra BM: {$bmName} ({$bmId})...");
            $totalCheckedBms++;

            // 1. Lấy danh sách account trên Meta API của BM này (duyệt hết tất cả các trang)
            $activeAccountIds = [];

            // Owner ads accounts (full pagination)
            $ownerRes = $this->metaBusinessService->getAllOwnerAdsAccountsForBm($bmId);
            if ($ownerRes->isError()) {
                $this->error("  ❌ Lỗi khi lấy owned accounts của BM {$bmId}: " . $ownerRes->getMessage());
                continue;
            }
            $ownerData = $ownerRes->getData() ?? [];
            foreach ($ownerData as $acc) {
                if (!empty($acc['id'])) {
                    $activeAccountIds[] = (string) $acc['id'];
                }
            }

            // Client ads accounts (full pagination)
            $clientRes = $this->metaBusinessService->getAllClientAdsAccountsForBm($bmId);
            if ($clientRes->isError()) {
                $this->error("  ❌ Lỗi khi lấy client accounts của BM {$bmId}: " . $clientRes->getMessage());
                continue;
            }
            $clientData = $clientRes->getData() ?? [];
            foreach ($clientData as $acc) {
                if (!empty($acc['id'])) {
                    $activeAccountIds[] = (string) $acc['id'];
                }
            }

            $activeAccountIds = array_values(array_unique($activeAccountIds));
            $this->line("  ✓ Meta API trả về " . count($activeAccountIds) . " tài khoản còn hoạt động trong BM.");

            // 2. Tìm các tài khoản trong DB từng thuộc BM này nhưng không còn trên Meta API
            $normalizedActiveIds = [];
            foreach ($activeAccountIds as $id) {
                $clean = preg_replace('/^act_/', '', trim((string) $id));
                $normalizedActiveIds[] = (string) $id;
                $normalizedActiveIds[] = $clean;
                $normalizedActiveIds[] = 'act_' . $clean;
            }
            $normalizedActiveIds = array_values(array_unique(array_filter($normalizedActiveIds)));

            $accessStaleQuery = \App\Models\MetaAccountBusinessManagerAccess::query()
                ->where('source_bm_id', $bmId);
            if (!empty($normalizedActiveIds)) {
                $accessStaleQuery->whereNotIn('account_id', $normalizedActiveIds);
            }
            $staleAccessIds = $accessStaleQuery->pluck('account_id')->toArray();

            $ownerStaleQuery = MetaAccount::query()
                ->where('business_manager_id', $bmId);
            if (!empty($normalizedActiveIds)) {
                $ownerStaleQuery->whereNotIn('account_id', $normalizedActiveIds);
            }
            $staleOwnerIds = $ownerStaleQuery->pluck('account_id')->toArray();

            $staleForThisBm = array_values(array_unique(array_merge($staleAccessIds, $staleOwnerIds)));

            if (empty($staleForThisBm)) {
                $this->info("  ✓ BM này hoàn toàn sạch sẽ, không có tài khoản rác bị xóa.");
                continue;
            }

            $this->warn("  ⚠️ Phát hiện " . count($staleForThisBm) . " tài khoản đã bị gỡ khỏi BM {$bmId}: " . implode(', ', $staleForThisBm));
            $totalStaleFound += count($staleForThisBm);

            if ($isDryRun) {
                continue;
            }

            // 3. Thực hiện dọn dẹp
            $this->metaService->detachStaleMetaAccountsForManager($bmId, $activeAccountIds);
            $totalDiscarded += count($staleForThisBm);
            $this->info("  ✓ Đã hoàn tất dọn dẹp tài khoản bị gỡ cho BM {$bmId}.");
        }

        $this->info("\n====================================================================");
        $this->info("  KẾT QUẢ DỌN DẸP:");
        $this->info("  - Tổng số BM đã kiểm tra: {$totalCheckedBms}");
        $this->info("  - Tổng số tài khoản bị gỡ khỏi BM phát hiện: {$totalStaleFound}");
        if (!$isDryRun) {
            $this->info("  - Tổng số tài khoản đã xử lý bỏ (soft delete/unassign): {$totalDiscarded}");
        }
        $this->info("====================================================================");

        return Command::SUCCESS;
    }
}
