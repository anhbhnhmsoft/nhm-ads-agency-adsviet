<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserReferral;

class UserReferralRepository extends BaseRepository
{
    protected function model(): UserReferral
    {
        return new UserReferral();
    }
}
