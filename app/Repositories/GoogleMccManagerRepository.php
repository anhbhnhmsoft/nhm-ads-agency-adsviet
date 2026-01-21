<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\GoogleMccManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GoogleMccManagerRepository extends BaseRepository
{
    protected function model(): GoogleMccManager
    {
        return new GoogleMccManager();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['parent_mcc_id'])) {
            $query->where('parent_mcc_id', $params['parent_mcc_id']);
        }
        if (isset($params['mcc_id'])) {
            $query->where('mcc_id', $params['mcc_id']);
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
        return $query->orderBy($column, $direction);
    }

    public function findByMccId(string $mccId): ?GoogleMccManager
    {
        return $this->model()->where('mcc_id', $mccId)->first();
    }

    public function findByParentMccId(string $parentMccId): Collection
    {
        return $this->model()->where('parent_mcc_id', $parentMccId)->get();
    }

    public function updateOrCreate(array $identifiers, array $data): GoogleMccManager
    {
        return $this->model()->updateOrCreate($identifiers, $data);
    }
}




