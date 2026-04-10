<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\GoogleAdsAccountInsight;
use Carbon\Carbon;

class GoogleAdsAccountInsightRepository extends BaseRepository
{
    protected function model(): GoogleAdsAccountInsight
    {
        return new GoogleAdsAccountInsight();
    }

    public function getTotalSpendForServiceUser(string $serviceUserId, Carbon $startDate, Carbon $endDate): float
    {
        return (float) $this->query()
            ->where('service_user_id', $serviceUserId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('spend');
    }
}

