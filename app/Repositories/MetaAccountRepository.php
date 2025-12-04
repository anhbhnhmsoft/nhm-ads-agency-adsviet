<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\MetaAccount;
use Illuminate\Database\Eloquent\Builder;

class MetaAccountRepository extends BaseRepository
{
    protected function model(): MetaAccount
    {
        return new MetaAccount();
    }

    public function filterQuery(Builder $query, array $params)
    {
        if (isset($params['service_user_id'])) {
            $query->where('service_user_id', $params['service_user_id']);
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
     * Lấy danh sách Meta Ads accounts có dấu hiệu hết tiền
     * - balance <= threshold
     * - hoặc amount_spent >= spend_cap (đối với tài khoản dùng spend cap)
     */
    public function getAccountsWithLowBalance(float $threshold): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model()
            ->newQuery()
            ->with(['serviceUser.user'])
            ->where(function ($query) use ($threshold) {
                $query->where(function ($balanceQuery) use ($threshold) {
                    $balanceQuery->whereNotNull('balance')
                        ->whereRaw('CAST(balance AS DECIMAL(20, 4)) <= ?', [$threshold]);
                })->orWhere(function ($spendCapQuery) {
                    $spendCapQuery->whereNotNull('spend_cap')
                        ->whereRaw('CAST(spend_cap AS DECIMAL(20, 4)) > 0')
                        ->whereNotNull('amount_spent')
                        ->whereRaw('CAST(amount_spent AS DECIMAL(20, 4)) >= CAST(spend_cap AS DECIMAL(20, 4))');
                });
            })
            ->get();
    }
}
