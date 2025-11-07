<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Builder;

class WalletRepository extends BaseRepository
{
    protected function model(): UserWallet
    {
        return new UserWallet();
    }

    public function findByUserId(int $userId): ?UserWallet
    {
        return $this->model()->where('user_id', $userId)->first();
    }

    public function filterQuery(array $filters = []): Builder
    {
        $query = $this->query();
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (int)$filters['status']);
        }
        return $query;
    }
}


