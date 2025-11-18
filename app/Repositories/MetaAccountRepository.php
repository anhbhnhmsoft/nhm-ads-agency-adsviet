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
}
