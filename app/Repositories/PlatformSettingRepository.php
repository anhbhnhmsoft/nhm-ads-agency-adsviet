<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Builder;

class PlatformSettingRepository extends BaseRepository
{
    protected function model(): PlatformSetting
    {
        return new PlatformSetting();
    }

    public function filterQuery(array $filters = []): Builder
    {
        $query = $this->query();

        if (!empty($filters['platform'])) {
            $query->where('platform', (int) $filters['platform']);
        }

        if (isset($filters['disabled'])) {
            $query->where('disabled', (bool) $filters['disabled']);
        }

        return $query;
    }

    public function sortQuery(Builder $query, string $column = 'id', string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'id';
        }
        $query->orderBy($column, $direction);
        if ($column !== 'id') {
            $query->orderBy('id', 'desc');
        }
        return $query;
    }

    public function findById(string $id): ?PlatformSetting
    {
        return $this->model()->find($id);
    }

    public function toggleDisabled(string $id, bool $disabled): bool
    {
        return (bool) $this->model()
            ->where('id', $id)
            ->update(['disabled' => $disabled]);
    }

    public function deactivateOthersByPlatform(int $platform, ?string $excludeId = null): int
    {
        $query = $this->model()
            ->where('platform', $platform)
            ->where('disabled', false);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->update(['disabled' => true]);
    }

    public function findActiveByPlatform(int $platform): ?PlatformSetting
    {
        return $this->model()
            ->where('platform', $platform)
            ->where('disabled', false)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function findByPlatform(int $platform): ?PlatformSetting
    {
        return $this->model()
            ->where('platform', $platform)
            ->orderBy('id', 'desc')
            ->first();
    }
}


