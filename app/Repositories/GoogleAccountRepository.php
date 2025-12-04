<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\GoogleAccount;
use Illuminate\Database\Eloquent\Builder;

class GoogleAccountRepository extends BaseRepository
{
    protected function model(): GoogleAccount
    {
        return new GoogleAccount();
    }

    public function filterQuery(Builder $query, array $params): Builder
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
     * Lấy danh sách Google Ads accounts có balance <= threshold
     * @param float $threshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountsWithLowBalance(float $threshold): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model()
            ->newQuery()
            ->with(['serviceUser.user'])
            ->whereNotNull('balance')
            ->where('balance', '<=', $threshold)
            ->whereHas('serviceUser.user', function ($query) {
                $query->where('disabled', false)
                    ->where(function ($subQuery) {
                        $subQuery->whereNotNull('telegram_id')
                            ->orWhere(function ($emailQuery) {
                                $emailQuery->whereNotNull('email')
                                    ->whereNotNull('email_verified_at');
                            });
                    });
            })
            ->get();
    }
}

