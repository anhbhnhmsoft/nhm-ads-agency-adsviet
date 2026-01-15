<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServiceUser;
use Illuminate\Database\Eloquent\Builder;

class ServiceUserRepository extends BaseRepository
{
    protected function model(): ServiceUser
    {
        return new ServiceUser();
    }

    public function filterQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['service_user_id'])) {
            $query->where('id', $filters['service_user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] === true) {
            $query->where('disabled', false);
        }

        if (!empty($filters['platform'])) {
            $query->whereHas('package', function ($packageQuery) use ($filters) {
                $packageQuery->where('platform', (int) $filters['platform']);
            });
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

    // Lấy dữ liệu cho bảng service users và referralBy cho user
    public function withListRelations(Builder $query): Builder
    {
        return $query->with([
            'package:id,name,platform,open_fee,top_up_fee',
            'user' => function ($userQuery) {
                $userQuery->select('id', 'name', 'referral_code')
                    ->with([
                        'referredBy' => function ($referredQuery) {
                            $referredQuery->select('id', 'referrer_id', 'referred_id')
                                ->with([
                                    'referrer:id,name,referral_code',
                                ]);
                        },
                    ]);
            },
        ]);
    }
}
