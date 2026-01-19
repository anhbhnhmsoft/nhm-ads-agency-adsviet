<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\MetaBusinessManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MetaBusinessManagerRepository extends BaseRepository
{
    protected function model(): MetaBusinessManager
    {
        return new MetaBusinessManager();
    }

    public function filterQuery(Builder $query, array $params)
    {
        if (isset($params['parent_bm_id'])) {
            $query->where('parent_bm_id', $params['parent_bm_id']);
        }
        if (isset($params['bm_id'])) {
            $query->where('bm_id', $params['bm_id']);
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

    public function findByBmId(string $bmId): ?MetaBusinessManager
    {
        return $this->model()->where('bm_id', $bmId)->first();
    }

    public function findByParentBmId(string $parentBmId): Collection
    {
        return $this->model()->where('parent_bm_id', $parentBmId)->get();
    }

    public function updateOrCreate(array $identifiers, array $data): MetaBusinessManager
    {
        return $this->model()->updateOrCreate($identifiers, $data);
    }
}

