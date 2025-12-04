<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale');

        if (!$locale) {
            $locale = $request->getPreferredLanguage($this->supportedLocales());
            $locale = $locale ?: config('app.locale');
            session(['locale' => $locale]);
        }

        if (!in_array($locale, $this->supportedLocales(), true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }

    protected function supportedLocales(): array
    {
        return ['vi', 'en'];
    }
}

