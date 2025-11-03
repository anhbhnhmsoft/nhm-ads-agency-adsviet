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
}
