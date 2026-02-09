<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\EmployeeCommission;
use Illuminate\Database\Eloquent\Builder;

class EmployeeCommissionRepository extends BaseRepository
{
    protected function model(): EmployeeCommission
    {
        return new EmployeeCommission();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['employee_id'])) {
            $query->where('employee_id', $params['employee_id']);
        }
        if (isset($params['service_package_id'])) {
            $query->where('service_package_id', $params['service_package_id']);
        }
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (isset($params['is_active'])) {
            $query->where('is_active', (bool) $params['is_active']);
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
     * Lấy cấu hình hoa hồng active cho nhân viên theo type
     */
    public function getActiveCommissionByType(string $employeeId, string $type): ?EmployeeCommission
    {
        return $this->query()
            ->where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Lấy cấu hình hoa hồng active cho gói dịch vụ theo type
     */
    public function getActiveCommissionByPackageAndType(string $packageId, string $type): ?EmployeeCommission
    {
        return $this->query()
            ->where('service_package_id', $packageId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }
}


