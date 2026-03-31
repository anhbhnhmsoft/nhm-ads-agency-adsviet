<?php

namespace App\Http\Middleware;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Service\PlatformSettingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @var PlatformSettingService
     */
    protected $platformSettingService;

    public function __construct(PlatformSettingService $platformSettingService)
    {
        $this->platformSettingService = $platformSettingService;
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $metaSettings = null;
        $googleSettings = null;

        // Chỉ share cấu hình BM cho Admin, Manager và Employee để Switch
        if ($user && in_array($user->role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
            $activeMeta = $this->platformSettingService->findPlatformActive(PlatformType::META->value)->getData();
            $metaSettings = [
                'current_id' => $activeMeta ? (string) $activeMeta->id : null,
                'list' => collect($this->platformSettingService->getAllActiveByPlatform(PlatformType::META->value)->getData())
                    ->map(fn($item) => ['id' => (string) $item->id, 'name' => 'BM - ' . ($item->name ?: substr($item->id, -4))])
                    ->toArray()
            ];

            $activeGoogle = $this->platformSettingService->findPlatformActive(PlatformType::GOOGLE->value)->getData();
            $googleSettings = [
                'current_id' => $activeGoogle ? (string) $activeGoogle->id : null,
                'list' => collect($this->platformSettingService->getAllActiveByPlatform(PlatformType::GOOGLE->value)->getData())
                    ->map(fn($item) => ['id' => (string) $item->id, 'name' => 'MCC - ' . ($item->name ?: substr($item->id, -4))])
                    ->toArray()
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => $user,
            'current_route' => fn () => request()->path(),
            'locale' => app()->getLocale(),
            'locales' => [
                ['code' => 'vi', 'label' => 'Tiếng Việt'],
                ['code' => 'en', 'label' => 'English'],
            ],
            'meta_settings' => $metaSettings,
            'google_settings' => $googleSettings,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info')
            ],
            'logo_path' => fn () => asset('images/logo-trans.png'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
