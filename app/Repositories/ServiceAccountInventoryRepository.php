<?php

namespace App\Repositories;

use App\Common\Constants\ServicePackage\ServiceAccountInventoryStatus;
use App\Core\BaseRepository;
use App\Models\ServiceAccountInventory;
use Illuminate\Database\Eloquent\Collection;

class ServiceAccountInventoryRepository extends BaseRepository
{
    protected function model(): ServiceAccountInventory
    {
        return new ServiceAccountInventory();
    }

    public function availableForPackage(string $packageId, int $platform, int $limit): Collection
    {
        return $this->query()
            ->where('service_package_id', $packageId)
            ->where('platform', $platform)
            ->where('status', ServiceAccountInventoryStatus::AVAILABLE->value)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->limit($limit)
            ->get();
    }

    public function releaseForServiceUser(string $serviceUserId): int
    {
        return $this->query()
            ->where('assigned_service_user_id', $serviceUserId)
            ->whereIn('status', [
                ServiceAccountInventoryStatus::RESERVED->value,
                ServiceAccountInventoryStatus::ASSIGNED->value,
            ])
            ->update([
                'status' => ServiceAccountInventoryStatus::AVAILABLE->value,
                'assigned_user_id' => null,
                'assigned_service_user_id' => null,
                'reserved_until' => null,
                'link_target_type' => null,
                'link_target_value' => null,
                'last_error' => null,
            ]);
    }
}
