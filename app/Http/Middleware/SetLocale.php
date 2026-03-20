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
        // 1. Kiểm tra tham số URL ?lang=vi hoặc ?locale=vi
        $locale = $request->query('lang') ?: $request->query('locale');

        // 2. Nếu không có, thử lấy từ Session (chỉ dành cho Web)
        if (!$locale && file_exists(storage_path('framework/sessions'))) {
            try {
                $locale = session('locale');
            } catch (\Throwable $e) {
                // Ignore session error in stateless requests
            }
        }

        // 3. Nếu vẫn không có, lấy từ Header Accept-Language
        if (!$locale) {
            $locale = $request->getPreferredLanguage($this->supportedLocales());
        }

        // 4. Fallback về cấu hình mặc định
        $locale = $locale ?: config('app.locale');

        // Kiểm tra tính hợp lệ của locale
        if (!in_array($locale, $this->supportedLocales(), true)) {
            $locale = config('app.locale');
        }

        // Đặt ngôn ngữ cho hệ thống
        app()->setLocale($locale);

        // Lưu vào session nếu có thể
        try {
            if ($request->hasSession()) {
                session(['locale' => $locale]);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $next($request);
    }

    protected function supportedLocales(): array
    {
        return ['vi', 'en'];
    }
}

