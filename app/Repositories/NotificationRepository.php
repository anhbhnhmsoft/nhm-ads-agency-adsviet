<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Notification;

class NotificationRepository extends BaseRepository
{
    protected function model(): Notification
    {
        return new Notification();
    }
}


