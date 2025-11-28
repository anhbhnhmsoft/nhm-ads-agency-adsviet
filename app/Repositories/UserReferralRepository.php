<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserReferral;

class UserReferralRepository extends BaseRepository
{
    protected function model(): UserReferral
    {
        return new UserReferral();
    }

    public function isEmployeeAssignedToManager(int $employeeId, int $managerId): bool
    {
        return $this->query()
            ->where('referrer_id', $managerId)
            ->where('referred_id', $employeeId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getAssignedEmployeeIds(int $managerId): array
    {
        return $this->query()
            ->where('referrer_id', $managerId)
            ->whereNull('deleted_at')
            ->pluck('referred_id')
            ->toArray();
    }

    public function assignEmployeeToManager(int $employeeId, int $managerId): bool
    {
        $existing = $this->query()
            ->withTrashed()
            ->where('referrer_id', $managerId)
            ->where('referred_id', $employeeId)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                return $existing->restore();
            }
            return true;
        }

        return (bool) $this->create([
            'referrer_id' => $managerId,
            'referred_id' => $employeeId,
        ]);
    }

    public function unassignEmployeeFromManager(int $employeeId, int $managerId): bool
    {
        $referral = $this->query()
            ->where('referrer_id', $managerId)
            ->where('referred_id', $employeeId)
            ->whereNull('deleted_at')
            ->first();

        if ($referral) {
            return (bool) $referral->delete();
        }

        return false;
    }

    /**
     * Lấy referrer của user (Manager hoặc Employee quản lý user này)
     */
    public function getReferrerByReferredId(int $referredId): ?UserReferral
    {
        return $this->query()
            ->where('referred_id', $referredId)
            ->whereNull('deleted_at')
            ->with(['referrer' => function ($query) {
                $query->select('id', 'name', 'username', 'role', 'telegram_id');
            }])
            ->first();
    }

    /**
     * Lấy tất cả referrer chain (nếu Employee quản lý Customer, thì cần cả Manager của Employee)
     */
    public function getReferrerChain(int $referredId): array
    {
        $referrers = [];
        $currentReferredId = $referredId;

        // Tìm tối đa 2 cấp: Employee -> Manager
        for ($i = 0; $i < 2; $i++) {
            $referral = $this->getReferrerByReferredId($currentReferredId);
            if (!$referral || !$referral->referrer) {
                break;
            }
            $referrers[] = $referral->referrer;
            $currentReferredId = $referral->referrer_id;
        }

        return $referrers;
    }
}
