<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\GoogleAdsAccountInsight;

class GoogleAdsAccountInsightRepository extends BaseRepository
{
    protected function model(): GoogleAdsAccountInsight
    {
        return new GoogleAdsAccountInsight();
    }
}

