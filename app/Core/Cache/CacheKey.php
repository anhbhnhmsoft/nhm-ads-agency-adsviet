<?php

namespace App\Core\Cache;

enum CacheKey: string
{
    case USER_PERMISSIONS = 'user.permissions';
    case FILE_STORAGE     = 'file.storage';
}
