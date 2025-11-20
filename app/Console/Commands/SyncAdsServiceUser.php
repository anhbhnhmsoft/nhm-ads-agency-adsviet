<?php

namespace App\Console\Commands;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Models\ServiceUser;
use App\Repositories\ServiceUserRepository;
use App\Service\MetaBusinessService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SyncAdsServiceUser extends Command
{
    protected $signature = 'app:sync-ads-service-user';

    protected $description = '(1h/lần) Đẩy Job vào queue để đồng bộ các ads account từ Meta hoặc Google Ads của Agency';

    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaBusinessService $metaBusinessService,
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->serviceUserRepository->query()
            ->with('package:id,platform')
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->chunkById(100, function (Collection $serviceUsers) {
                $serviceUsers->each(function (ServiceUser $serviceUser) {
                    // đối với từng service user, kiểm tra nền tảng và đẩy job tương ứng
                    // nếu là nền tảng Meta, đẩy job đồng bộ Meta
                    if ($serviceUser->package->platform === PlatformType::META->value) {
                        // Đẩy job đồng bộ Meta
                        \App\Jobs\MetaApi\SyncMetaJob::dispatch($serviceUser);
                    }
                    // Google Ads có thể thêm ở đây sau
                });
            });

        return Command::SUCCESS;
    }
}
