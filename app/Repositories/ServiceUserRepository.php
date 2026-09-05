<?php

namespace App\Repositories;

use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\BaseRepository;
use App\Models\ServiceUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceUserRepository extends BaseRepository
{
    protected function model(): ServiceUser
    {
        return new ServiceUser();
    }

    public function filterQuery(array $filters = []): Builder
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

        // Tìm kiếm theo tên khách hàng hoặc username
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', $search)
                    ->orWhere('username', 'like', $search);
            });
        }

        // Lọc theo trạng thái ví / thanh toán
        if (!empty($filters['billing_status'])) {
            if ($filters['billing_status'] === 'low_balance') {
                $query->whereHas('user.wallet', function ($walletQuery) {
                    $walletQuery->where('balance', '<', 100);
                });
            } elseif ($filters['billing_status'] === 'healthy') {
                $query->whereHas('user.wallet', function ($walletQuery) {
                    $walletQuery->where('balance', '>=', 100);
                });
            }
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
            'package:id,name,platform,payment_type,billing_source,open_fee,top_up_fee,spending_fee',
            'metaAccount:id,service_user_id,account_id,business_manager_id,amount_spent,currency',
            'googleAccounts:id,service_user_id,account_id,customer_manager_id,amount_spent,currency',
            'user' => function ($userQuery) {
                $userQuery->select('id', 'name', 'username', 'referral_code')
                    ->with([
                        'wallet:id,user_id,balance',
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
    public function getActiveServicesWithCashback(): Collection
    {
        return $this->query()
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->whereHas('package', function ($query) {
                $query->whereNotNull('monthly_spending_fee_structure');
            })
            ->with(['package', 'user.wallet'])
            ->get();
    }

    public function getActiveServicesWithRefundOpenFee(): Collection
    {
        return $this->query()
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->whereHas('package', function ($query) {
                $query->where('refund_open_fee', true)
                    ->whereNotNull('min_spend_for_refund')
                    ->where('min_spend_for_refund', '>', 0);
            })
            ->with(['package', 'user.wallet'])
            ->get();
    }
}
