<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\CommissionTransaction;
use Illuminate\Database\Eloquent\Builder;

class CommissionTransactionRepository extends BaseRepository
{
    protected function model(): CommissionTransaction
    {
        return new CommissionTransaction();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['employee_id'])) {
            $query->where('employee_id', $params['employee_id']);
        }
        if (isset($params['customer_id'])) {
            $query->where('customer_id', $params['customer_id']);
        }
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (isset($params['period'])) {
            $query->where('period', $params['period']);
        }
        if (isset($params['is_paid'])) {
            $query->where('is_paid', (bool) $params['is_paid']);
        }
        if (isset($params['date_from'])) {
            $query->where('created_at', '>=', $params['date_from']);
        }
        if (isset($params['date_to'])) {
            $query->where('created_at', '<=', $params['date_to']);
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
     * Lấy tổng hoa hồng theo nhân viên và period
     */
    public function getTotalCommissionByPeriod(string $employeeId, string $period): float
    {
        return (float) $this->query()
            ->where('employee_id', $employeeId)
            ->where('period', $period)
            ->where('is_paid', false)
            ->sum('commission_amount');
    }

    /**
     * Đánh dấu hoa hồng đã thanh toán
     */
    public function markAsPaid(array $ids, ?string $paidAt = null): int
    {
        return $this->query()
            ->whereIn('id', $ids)
            ->update([
                'is_paid' => true,
                'paid_at' => $paidAt ?? now()->format('Y-m-d'),
            ]);
    }
}


