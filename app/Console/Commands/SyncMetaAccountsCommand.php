<?php

namespace App\Console\Commands;

use App\Common\Constants\Platform\PlatformType;
use App\Models\PlatformSetting;
use App\Service\MetaService;
use App\Service\PlatformSettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMetaAccountsCommand extends Command
{
    protected $signature = 'meta:sync-accounts {bm_id? : ID của BM cụ thể cần đồng bộ}';

    protected $description = 'Đồng bộ tài khoản quảng cáo từ Business Managers của Meta về hệ thống để sẵn sàng gán cho khách';

    public function handle(MetaService $metaService, PlatformSettingService $platformSettingService): int
    {
        $specificBmId = $this->argument('bm_id');

        $this->info('Bắt đầu đồng bộ tài khoản Meta...');

        $metaSettings = $platformSettingService->getAllActiveByPlatform(PlatformType::META->value);
        if ($metaSettings->isError() || empty($metaSettings->getData())) {
            $this->error('Không tìm thấy cấu hình Meta nào đang hoạt động.');
            return self::FAILURE;
        }

        foreach ($metaSettings->getData() as $setting) {
            $settingId = (string) $setting->id;
            $this->info("Đang đồng bộ cho Platform Setting: {$setting->name} (ID: {$settingId})");

            if ($specificBmId) {
                $this->info("Đang đồng bộ BM cụ thể: {$specificBmId}...");
                $res = $metaService->syncFromBusinessManagerId($specificBmId, $settingId);
            } else {
                $this->info("Đang đồng bộ tất cả Business Managers có quyền truy cập...");
                $res = $metaService->syncFromAccessibleBusinessManagers($settingId);
            }

            if ($res->isSuccess()) {
                $this->info("✓ Đồng bộ thành công cho setting ID {$settingId}.");
            } else {
                $this->warn("⚠ Đồng bộ kết thúc với cảnh báo: " . $res->getMessage());
            }
        }

        // Đảm bảo đồng bộ liên kết bảng meta_account_business_manager_accesses cho các tài khoản có business_manager_id
        $this->info('Đang tối ưu liên kết BM cho các tài khoản...');
        $unlinkedAccounts = DB::table('meta_accounts')
            ->whereNotNull('business_manager_id')
            ->whereNull('deleted_at')
            ->get(['account_id', 'business_manager_id']);

        $linkedCount = 0;
        foreach ($unlinkedAccounts as $acc) {
            $exists = DB::table('meta_account_business_manager_accesses')
                ->where('account_id', $acc->account_id)
                ->where('source_bm_id', $acc->business_manager_id)
                ->exists();

            if (!$exists) {
                DB::table('meta_account_business_manager_accesses')->insert([
                    'account_id' => $acc->account_id,
                    'source_bm_id' => $acc->business_manager_id,
                    'owner_bm_id' => $acc->business_manager_id,
                    'last_synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $linkedCount++;
            }
        }

        $this->info("✓ Đã hoàn tất đồng bộ! Thêm {$linkedCount} liên kết BM mới cho các tài khoản.");

        return self::SUCCESS;
    }
}
