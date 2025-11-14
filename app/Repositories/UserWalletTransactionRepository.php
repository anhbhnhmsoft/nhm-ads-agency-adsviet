<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserWalletTransaction;

class UserWalletTransactionRepository extends BaseRepository
{
    protected function model(): UserWalletTransaction
    {
        return new UserWalletTransaction();
    }

    public function create(array $attributes): UserWalletTransaction
    {
        return $this->model()->create($attributes);
    }

    public function updateById(int $id, array $attributes): bool
    {
        return (bool) $this->query()->where('id', $id)->update($attributes);
    }
}



