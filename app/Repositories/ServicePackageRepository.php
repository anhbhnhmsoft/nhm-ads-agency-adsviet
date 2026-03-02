<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageRepository extends BaseRepository
{
    protected function model(): ServicePackage
    {
        return new ServicePackage();
    }


    public function filterQuery(array $filters = [])
    {
        $query = $this->model()->query();

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['is_active'])) {
            $query->where('disabled', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN) === false);
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
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

}
