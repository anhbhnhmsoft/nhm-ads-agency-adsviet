<?php

namespace App\Repositories;

use App\Common\Constants\ServicePackage\Meta\MetaAdsAccountStatus;
use App\Core\BaseRepository;
use App\Models\MetaAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MetaAccountRepository extends BaseRepository
{
    protected function model(): MetaAccount
    {
        return new MetaAccount;
    }

    public function filterQuery(Builder $query, array $params)
    {
        if (isset($params['service_user_id'])) {
            $query->where('service_user_id', $params['service_user_id']);
        }

        return $query;
    }

    /**
     * Sắp xếp query dựa trên cột và hướng
     */
    public function sortQuery(Builder $query, string $column, string $direction = 'desc'): Builder
    {
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);

        return $query;
    }

    /**
     * Lấy danh sách Meta Ads accounts có dấu hiệu hết tiền
     * - balance <= threshold
     * - hoặc amount_spent >= spend_cap (đối với tài khoản dùng spend cap)
     */
    public function getAccountsWithLowBalance(float $_threshold): Collection
    {
        return $this->model()
            ->newQuery()
            ->with(['serviceUser.user'])
            // Chỉ gửi cảnh báo cho các tài khoản còn đang hoạt động
            ->whereNotIn('account_status', [
                MetaAdsAccountStatus::DISABLED->value,
                MetaAdsAccountStatus::CLOSED->value,
                MetaAdsAccountStatus::PENDING_CLOSURE->value,
                MetaAdsAccountStatus::ANY_CLOSED->value,
            ])
            ->where(function ($query) {
                // Meta monetary fields use minor units; the service applies the
                // currency-aware USD threshold after loading candidates.
                $query->where(function ($balanceQuery) {
                    $balanceQuery->whereNotNull('balance');
                })->orWhere(function ($spendCapQuery) {
                    $spendCapQuery->whereNotNull('spend_cap')
                        ->whereRaw('CAST(spend_cap AS DECIMAL(20, 4)) > 0')
                        ->whereNotNull('amount_spent')
                        ->whereRaw('CAST(amount_spent AS DECIMAL(20, 4)) >= CAST(spend_cap AS DECIMAL(20, 4))');
                });
            })
            ->get();
    }
}
