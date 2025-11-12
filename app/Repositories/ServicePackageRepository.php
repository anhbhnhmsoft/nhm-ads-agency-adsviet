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

        if (isset($filters['is_active']) && $filters['is_active'] === true) {
            $query->where('disabled', false);
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
