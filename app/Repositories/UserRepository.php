<?php

namespace App\Repositories;

use App\Common\Constants\User\UserRole;
use App\Core\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    protected function model(): User
    {
        return new User();
    }

    public function filterQuery(array $filters): Builder
    {
        $query = $this->query();
        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', "%{$filters['keyword']}%")
                ->orWhere('username', 'like', "%{$filters['keyword']}%")
                ->orWhere('id', (int)$filters['keyword']);
        }
        if (!empty($filters['roles'])) {
            $query->whereIn('role', $filters['roles']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, string $column, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }


    /**
     * Check username in admin system
     * @param string $username
     * @return bool
     */
    public function checkUsernameAdminSystem(string $username): bool
    {
        return $this->model()
            ->isActive()
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
        return $this->model()
            ->isActive()
            ->where('username', $username)
            ->whereIn('role', [
                UserRole::AGENCY->value,
                UserRole::CUSTOMER->value,
            ])
            ->exists();
    }


    /**
     * Get user by telegram id
     * @param string $telegramId
     * @return User|null
     */
    public function getUserByTelegramId(string $telegramId): ?User
    {
        return $this->model()
            ->isActive()
            ->where('telegram_id', $telegramId)
            ->first();
    }

    /**
     * Lấy user giới thiệu để đăng ký
     * Cần phải là role AGENCY hoặc EMPLOYEE hoặc MANAGER
     * @param string $referCode
     * @return User|null
     */
    public function getUserToRegisterByReferCode(string $referCode): ?User
    {
        return $this->model()
            ->isActive()
            ->where('referral_code', $referCode)
            ->whereIn('role', [
                UserRole::AGENCY->value,
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
            ])
            ->first();
    }
}
