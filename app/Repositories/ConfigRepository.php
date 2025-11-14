<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Config;
use Illuminate\Database\Eloquent\Collection;

class ConfigRepository extends BaseRepository
{
    protected function model(): Config
    {
        return new Config();
    }

    public function findByKey(string $key): ?Config
    {
        return $this->query()->where('key', $key)->first();
    }

    public function findAll(): Collection
    {
        return $this->query()->get();
    }

    public function updateByKey(string $key, array $data): bool
    {
        return (bool) $this->query()
            ->where('key', $key)
            ->update($data);
    }

    public function updateMany(array $configs): bool
    {
        foreach ($configs as $key => $value) {
            $this->updateByKey($key, ['value' => $value]);
        }
        return true;
    }
}

