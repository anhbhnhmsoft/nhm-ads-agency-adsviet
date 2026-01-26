<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class SupplierRepository extends BaseRepository
{
    protected function model(): Supplier
    {
        return new Supplier();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['disabled'])) {
            $query->where('disabled', (bool) $params['disabled']);
        }
        if (isset($params['keyword'])) {
            $keyword = trim($params['keyword']);
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            }
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

