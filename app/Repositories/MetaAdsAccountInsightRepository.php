<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\MetaAdsAccountInsight;

class MetaAdsAccountInsightRepository extends BaseRepository
{
    protected function model(): MetaAdsAccountInsight
    {
        return new MetaAdsAccountInsight();
    }

    public function getTotalSpendForServiceUser(string $serviceUserId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        return (float) $this->query()
            ->where('service_user_id', $serviceUserId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('spend');
    }
}
