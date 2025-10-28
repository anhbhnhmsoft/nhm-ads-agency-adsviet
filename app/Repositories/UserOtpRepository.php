<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserOtp;

class UserOtpRepository extends BaseRepository
{
    protected function model(): UserOtp
    {
        return new UserOtp();
    }
}
