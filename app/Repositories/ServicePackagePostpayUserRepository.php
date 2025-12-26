<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServicePackagePostpayUser;
use Illuminate\Database\Eloquent\Model;

class ServicePackagePostpayUserRepository extends BaseRepository
{
    protected function model(): Model
    {
        return new ServicePackagePostpayUser();
    }

    /**
     * Lấy danh sách user IDs được phép trả sau cho một gói dịch vụ
     */
    public function getPostpayUserIdsByPackageId(int|string $servicePackageId): array
    {
        return $this->query()
            ->where('service_package_id', $servicePackageId)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Kiểm tra user có được phép trả sau cho gói dịch vụ không
     */
    public function isUserAllowedPostpay(int|string $servicePackageId, int|string $userId): bool
    {
        return $this->query()
            ->where('service_package_id', $servicePackageId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Đồng bộ danh sách users được phép trả sau cho gói dịch vụ
     */
    public function syncPostpayUsers(int|string $servicePackageId, array $userIds): void
    {
        $currentRecords = $this->query()
            ->withTrashed()
            ->where('service_package_id', $servicePackageId)
            ->get()
            ->keyBy('user_id');

        $currentUserIds = $currentRecords->keys()->map(fn($id) => (string)$id)->toArray();
        $newUserIds = array_map(fn($id) => (string)$id, $userIds);

        // Users cần xóa (có trong hiện tại nhưng không có trong danh sách mới)
        $userIdsToDelete = array_diff($currentUserIds, $newUserIds);
        if (!empty($userIdsToDelete)) {
            $this->query()
                ->where('service_package_id', $servicePackageId)
                ->whereIn('user_id', $userIdsToDelete)
                ->delete();
        }

        // Users cần thêm hoặc restore (có trong danh sách mới)
        $userIdsToAdd = array_diff($newUserIds, $currentUserIds);
        $userIdsToRestore = array_intersect($newUserIds, $currentUserIds);

        // Restore những user đã bị soft delete nhưng có trong danh sách mới
        if (!empty($userIdsToRestore)) {
            $this->query()
                ->withTrashed()
                ->where('service_package_id', $servicePackageId)
                ->whereIn('user_id', $userIdsToRestore)
                ->whereNotNull('deleted_at')
                ->restore();
        }

        // Tạo mới những user chưa có trong database
        if (!empty($userIdsToAdd)) {
            $data = array_map(function ($userId) use ($servicePackageId) {
                return [
                    'service_package_id' => $servicePackageId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $userIdsToAdd);

            $this->createMany($data);
        }
    }
}

