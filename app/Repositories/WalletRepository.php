<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserWallet;
use App\Common\Constants\Wallet\WalletStatus;
use App\Common\Constants\User\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class WalletRepository extends BaseRepository
{
    protected function model(): UserWallet
    {
        return new UserWallet();
    }

    public function findByUserId(string $userId): ?UserWallet
    {
        return $this->model()->where('user_id', $userId)->first();
    }

    public function filterQuery(array $filters = []): Builder
    {
        $query = $this->query();
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (string)$filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (int)$filters['status']);
        }
        return $query;
    }

    public function getCustomersWithLowBalance(float $threshold): Collection
    {
        return $this->model()
            ->newQuery()
            ->with('user')
            ->where('status', WalletStatus::ACTIVE->value)
            ->where('balance', '<=', $threshold)
            ->whereHas('user', function ($query) {
                $query->where('disabled', false)
                    ->whereIn('role', [
                        UserRole::CUSTOMER->value,
                        UserRole::AGENCY->value,
                    ])
                    ->where(function ($subQuery) {
                        $subQuery->whereNotNull('telegram_id')
                            ->orWhere(function ($emailQuery) {
                                $emailQuery->whereNotNull('email')
                                    ->whereNotNull('email_verified_at');
                            });
                    });
            })
            ->get();
    }
}


