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

    public function isEmployeeAssignedToManager(string $employeeId, string $managerId): bool
    {
        return $this->query()
            ->where('referrer_id', $managerId)
            ->where('referred_id', $employeeId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getAssignedEmployeeIds(string $managerId): array
    {
        return $this->query()
            ->where('referrer_id', $managerId)
            ->whereNull('deleted_at')
            ->pluck('referred_id')
            ->toArray();
    }

    public function assignEmployeeToManager(string $employeeId, string $managerId): bool
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

    public function unassignEmployeeFromManager(string $employeeId, string $managerId): bool
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
    public function getReferrerByReferredId(string $referredId): ?UserReferral
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
    public function getReferrerChain(string $referredId): array
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
