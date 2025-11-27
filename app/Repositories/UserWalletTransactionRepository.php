<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserWalletTransaction;
use Illuminate\Database\Eloquent\Builder;

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

    public function queryFilter(Builder $query,array $filters = []): Builder
    {
        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }
        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
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



