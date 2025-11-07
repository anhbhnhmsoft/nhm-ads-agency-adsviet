<?php

namespace App\Core\Cache;

enum CacheKey: string
{
    case CACHE_TELEGRAM_ID = 'CACHE_TELEGRAM_ID';
    case CACHE_TELEGRAM_OTP = 'CACHE_TELEGRAM_OTP';
}
