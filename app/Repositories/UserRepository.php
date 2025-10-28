<?php

namespace App\Repositories;

use App\Common\Constants\User\UserRole;
use App\Core\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    protected function model(): User
    {
        return new User();
    }

    /**
     * Check username in admin system
     * @param string $username
     * @return bool
     */
    public function checkUsernameAdminSystem(string $username): bool
    {
        return $this->query()
            ->where('username', $username)
            ->whereIn('role', [
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ])
            ->exists();
    }

    /**
     * Check username in customer system
     * @param string $username
     * @return bool
     */
    public function checkUsernameCustomerSystem(string $username): bool
    {
        return $this->query()
            ->where('username', $username)
            ->whereIn('role', [
                UserRole::AGENCY->value,
                UserRole::CUSTOMER->value,
            ])
            ->exists();
    }
}
