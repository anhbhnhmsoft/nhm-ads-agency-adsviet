<?php

namespace App\Repositories;

use App\Common\Constants\User\UserRole;
use App\Core\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    protected function model(): User
    {
        return new User();
    }

    /**
     * Lọc query dựa trên các tiêu chí tìm kiếm
     * @param array $filters
     * @return Builder
     */
    public function filterQuery(array $filters): Builder
    {
        $query = $this->query();
        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('referral_code', 'like', "%{$keyword}%")
                    ->orWhere('id', 'like', "%{$keyword}%");
            });
        }
        if (!empty($filters['roles'])) {
            $query->whereIn('role', $filters['roles']);
        }

        if (!empty($filters['username'])) {
            $query->where('username', $filters['username']);
        }
        if (isset($filters['has_telegram']) && $filters['has_telegram'] === true) {
            $query->where('telegram_id', '!=', null);
        }

        if (isset($filters['is_active']) && $filters['is_active'] === true) {
            $query->where('disabled', false);
        }

        // Chỉ lấy những user có bản ghi liên kết (user_referrals) mà referrer_id thuộc danh sách cho phép
        if (!empty($filters['referrer_ids']) && is_array($filters['referrer_ids'])) {
            $referrerIds = array_filter($filters['referrer_ids']);
            if (!empty($referrerIds)) {
                $query->whereHas('referredBy', function ($q) use ($referrerIds) {
                    $q->whereIn('referrer_id', $referrerIds)
                        ->whereNull('deleted_at');
                });
            }
        }

        // Agency không thấy chính mình
        if (!empty($filters['exclude_user_id'])) {
            $query->where('id', '!=', $filters['exclude_user_id']);
        }

        // Nếu có manager_id trả về từ service thì lấy employe của manager đó
        if (!empty($filters['manager_id'])) {
            $query->whereHas('referredBy', function ($q) use ($filters) {
                $q->where('referrer_id', $filters['manager_id'])
                    ->whereNull('deleted_at');
            });
        }
        return $query;
    }

    /**
     * Sắp xếp query dựa trên cột và hướng
     * @param Builder $query
     * @param string $column
     * @param string $direction
     * @return Builder
     */
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
     * Lấy user theo username
     * @param string $username
     * @return User|null
     */
    public function getUserByUsername(string $username): ?User
    {
        return $this->model()
            ->isActive()
            ->where('username', $username)
            ->first();
    }


    /**
     * Get user by telegram id
     * @param string $telegramId
     * @return User|null
     */
    public function getUserByTelegramId(string $telegramId): ?User
    {
        return $this->query()
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

    public function queryEmployees(): Builder
    {
        return $this->model()
            ->whereIn('role', [UserRole::EMPLOYEE->value, UserRole::MANAGER->value]);
    }

    public function queryUser(): Builder
    {
        return $this->model()
            ->whereIn('role', [UserRole::CUSTOMER->value, UserRole::AGENCY->value]);
    }

    public function listEmployees(array $filters = [])
    {
        $query = $this->queryEmployees();
        if (!empty($filters['username'])) {
            $query->where('username', 'like', '%' . $filters['username'] . '%');
        }
        if (isset($filters['role'])) {
            $query->where('role', (int)$filters['role']);
        }
        if (isset($filters['disabled'])) {
            $query->where('disabled', $filters['disabled']);
        }
        return $query->orderByDesc('id')
        ->get(['id','name','username','role','disabled','referral_code']);
    }

    public function findEmployeeById(string $id): ?User
    {
        return $this->queryEmployees()
        ->find($id);
    }

    public function findUserById(string $id): ?User
    {
        return $this->queryUser()
        ->find($id);
    }

    public function toggleDisableById(string $id, bool $disabled): bool
    {
        return (bool) $this->model()
        ->where('id', $id)
        ->update(['disabled' => $disabled]);
    }

    public function getManagers(): Collection
    {
        return $this->model()
            ->where('role', UserRole::MANAGER->value)
            ->where('disabled', false)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    public function getEmployees(): Collection
    {
        return $this->model()
            ->where('role', UserRole::EMPLOYEE->value)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    public function countTotalCustomers(): int
    {
        return $this->queryUser()->count();
    }

    public function countActiveCustomers(): int
    {
        return $this->queryUser()
            ->where('disabled', false)
            ->count();
    }
}
