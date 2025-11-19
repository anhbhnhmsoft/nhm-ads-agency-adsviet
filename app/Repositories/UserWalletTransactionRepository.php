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

    public function updateById(string|int $id, array $attributes): bool
    {
        return (bool) $this->query()->where('id', $id)->update($attributes);
    }

    // Tìm transaction theo reference_id và type
    public function findByReferenceId(string $referenceId, int $type): ?UserWalletTransaction
    {
        return $this->query()
            ->where('reference_id', $referenceId)
            ->where('type', $type)
            ->first();
    }

    public function filterForWallet(int $walletId, array $filters = [])
    {
        $query = $this->query()->where('wallet_id', $walletId);

        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['network'])) {
            $query->where('network', $filters['network']);
        }

        return $query;
    }
}



