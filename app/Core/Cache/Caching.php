<?php

namespace App\Core\Cache;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class Caching
{
    /**
     * Get cache value by key and unique key
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return mixed|null
     */
    public static function getCache(CacheKey $key, string $uniqueKey = null)
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::get($cacheKey);
    }

    /**
     * Set cache (toàn cục)
     * @param CacheKey $key
     * @param $value
     * @param string|null $uniqueKey
     * @param int $expire Expire time in minutes
     * @return bool
     */
    public static function setCache(CacheKey $key, $value, string $uniqueKey = null, int $expire = 60): bool
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::put($cacheKey, $value, now()->addMinutes($expire));
    }

    public static function clearCache(CacheKey $key, string $uniqueKey = null): bool
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all cache
     * Không khuyến khích sử dụng trong môi trường production vì nó sẽ xóa toàn bộ cache
     * @return bool
     */
    public function flushCache(): bool
    {
        return Cache::flush();
    }

    /**
     * Chỉ sử dụng khi đã đăng nhập
     * @param CacheKey $key
     * @param $value
     * @param string|null $uniqueKey
     * @param int $expire
     * @return bool
     */
    public static function setSessionCache(CacheKey $key, $value, string $uniqueKey = null, int $expire = 60): bool
    {
        if (!auth()->check()) {
            return false;
        }
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return request()->session()->cache()->put($cacheKey, $value, $expire);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getSessionCache(CacheKey $key, string $uniqueKey = null)
    {
        if (!auth()->check()) {
            return null;
        }
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return request()->session()->cache()->get($cacheKey);
    }

    /**
     * Chỉ sử dụng khi đã đăng nhập
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return bool
     */
    public static function clearSessionCache(CacheKey $key, string $uniqueKey = null): bool
    {
        if (!auth()->check()) {
            return false;
        }
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return request()->session()->cache()->forget($cacheKey);
    }
    /**
     * Chỉ sử dụng khi đã đăng nhập
     * Clear all session cache
     * @return bool
     */
    public static function flushCacheSession(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        return request()->session()->cache()->flush();
    }


}
