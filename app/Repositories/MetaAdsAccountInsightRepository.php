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
}
