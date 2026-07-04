<?php

namespace App\Core;

use App\Models\User;
use Illuminate\Support\Facades\App;

class UserLocale
{
    private const SUPPORTED = ['vi', 'en', 'zh'];
    private const FALLBACK = 'en';

    public static function resolve(?User $user): string
    {
        $lang = $user?->language;
        if ($lang && in_array($lang, self::SUPPORTED, true)) {
            return $lang;
        }
        return self::FALLBACK;
    }

    /**
     * Run $callback with the app locale set to $user's preferred language
     * (fallback 'en' when user has not chosen one). The original locale is
     * always restored, even on exception.
     */
    public static function run(?User $user, callable $callback)
    {
        $original = App::getLocale();
        App::setLocale(self::resolve($user));
        try {
            return $callback();
        } finally {
            App::setLocale($original);
        }
    }
}
