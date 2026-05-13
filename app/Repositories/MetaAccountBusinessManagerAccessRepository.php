<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\MetaAccountBusinessManagerAccess;

class MetaAccountBusinessManagerAccessRepository extends BaseRepository
{
    protected function model(): MetaAccountBusinessManagerAccess
    {
        return new MetaAccountBusinessManagerAccess();
    }
}
