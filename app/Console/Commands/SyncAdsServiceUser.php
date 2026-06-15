<?php

namespace App\Console\Commands;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Jobs\GoogleAds\SyncGoogleServiceUserJob;
use App\Jobs\MetaApi\SyncMetaJob;
use App\Models\ServiceUser;
use App\Repositories\ServiceUserRepository;
use App\Service\MetaBusinessService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SyncAdsServiceUser extends Command
{
    protected $signature = 'app:sync-ads-service-user';

    protected $description = '(5 phút/lần) Đẩy Job vào queue để đồng bộ các ads account từ Meta hoặc Google Ads của Agency';

    private const DISPATCH_BATCH_SIZE = 50;

    private const DISPATCH_BATCH_DELAY_SECONDS = 15;

    private const FRESH_SYNC_SKIP_SECONDS = 300;

    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaBusinessService $metaBusinessService,
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $totalFound = 0;
        $totalDispatched = 0;
        $this->serviceUserRepository->query()
            ->with(['package'])
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->chunkById(100, function (Collection $serviceUsers) use (&$totalFound, &$totalDispatched) {
                $totalFound += $serviceUsers->count();
                $serviceUsers->each(function (ServiceUser $serviceUser) use (&$totalDispatched) {
                    // đối với từng service user, kiểm tra nền tảng và đẩy job tương ứng
                    // nếu là nền tảng Meta, đẩy job đồng bộ Meta
                    if (!$serviceUser->package) {
                        $this->error("Lỗi: ServiceUser ID {$serviceUser->id} không có gói dịch vụ hoặc gói dịch vụ đã bị xóa.");
                        return;
                    }

                    if ($this->hasFreshSyncedData($serviceUser)) {
                        $this->info("↷ Bỏ qua ServiceUser ID: {$serviceUser->id} vì dữ liệu vừa được sync gần đây");
                        return;
                    }

                    $batchIndex = intdiv($totalDispatched, self::DISPATCH_BATCH_SIZE);
                    $delaySeconds = $batchIndex * self::DISPATCH_BATCH_DELAY_SECONDS;

                    if ($serviceUser->package->platform === PlatformType::META->value) {
                        SyncMetaJob::dispatch($serviceUser)->delay(now()->addSeconds($delaySeconds));
                        $totalDispatched++;
                        $this->info("✓ Đã dispatch job sync Meta cho ServiceUser ID: {$serviceUser->id} sau {$delaySeconds}s");
                    }

                    if ($serviceUser->package->platform === PlatformType::GOOGLE->value) {
                        SyncGoogleServiceUserJob::dispatch($serviceUser)->delay(now()->addSeconds($delaySeconds));
                        $totalDispatched++;
                        $this->info("✓ Đã dispatch job sync Google Ads cho ServiceUser ID: {$serviceUser->id} sau {$delaySeconds}s");
                    }
                });
            });

        return Command::SUCCESS;
    }

    private function hasFreshSyncedData(ServiceUser $serviceUser): bool
    {
        $freshAfter = now()->subSeconds(self::FRESH_SYNC_SKIP_SECONDS);
        $serviceUserId = (string) $serviceUser->id;

        if ($serviceUser->package->platform === PlatformType::META->value) {
            return $this->hasRecentTimestamp('meta_accounts', $serviceUserId, $freshAfter)
                || $this->hasRecentTimestamp('meta_ads_campaigns', $serviceUserId, $freshAfter)
                || $this->hasRecentTimestamp('meta_ads_account_insights', $serviceUserId, $freshAfter);
        }

        if ($serviceUser->package->platform === PlatformType::GOOGLE->value) {
            return $this->hasRecentTimestamp('google_accounts', $serviceUserId, $freshAfter)
                || $this->hasRecentTimestamp('google_ads_campaigns', $serviceUserId, $freshAfter)
                || $this->hasRecentTimestamp('google_ads_account_insights', $serviceUserId, $freshAfter);
        }

        return false;
    }

    private function hasRecentTimestamp(string $table, string $serviceUserId, Carbon $freshAfter): bool
    {
        return DB::table($table)
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->where('last_synced_at', '>=', $freshAfter)
            ->exists();
    }
}
