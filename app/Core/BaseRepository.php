<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    abstract protected function model(): Model;

    public function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model()->newQuery();
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model()::query();
    }

    public function createMany(array $attributes): bool
    {
        return $this->model()->insert($attributes);
    }

    public function create(array $attributes): Model
    {
        return $this->model()->create($attributes);
    }

    public function find(int $id): ?Model
    {
        return $this->model()->find($id);
    }

    public function findMany(array $ids): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model()->whereIn('id', $ids)->get();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model()->all();
    }

    public function delete(int $id): bool
    {
        return $this->model()->destroy($id);
    }

    public function deleteMany(array $ids): bool
    {
        return $this->model()->whereIn('id', $ids)->delete();
    }


}
