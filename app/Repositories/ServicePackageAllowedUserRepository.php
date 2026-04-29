<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServicePackageAllowedUser;
use Illuminate\Database\Eloquent\Model;

class ServicePackageAllowedUserRepository extends BaseRepository
{
    protected function model(): Model
    {
        return new ServicePackageAllowedUser();
    }

    public function getAllowedUserIdsByPackageId(int|string $servicePackageId): array
    {
        return $this->query()
            ->where('service_package_id', $servicePackageId)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function hasAllowedUsers(int|string $servicePackageId): bool
    {
        return $this->query()
            ->where('service_package_id', $servicePackageId)
            ->exists();
    }

    public function isUserAllowed(int|string $servicePackageId, int|string $userId): bool
    {
        return $this->query()
            ->where('service_package_id', $servicePackageId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function syncAllowedUsers(int|string $servicePackageId, array $userIds): void
    {
        $this->query()
            ->where('service_package_id', $servicePackageId)
            ->delete();

        $rows = collect($userIds)
            ->map(fn ($userId) => [
                'service_package_id' => $servicePackageId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rows)) {
            $this->query()->insert($rows);
        }
    }
}
