<?php

namespace App\Providers;

use App\Common\Constants\User\GatePermission;
use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use App\Service\AuthService;
use App\Service\TelegramService;
use App\Service\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerRepository();
        $this->registerApplicationService();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->definedGate();
    }

    private function registerRepository(): void
    {
        $this->app->bind(UserRepository::class);
        $this->app->bind(UserOtpRepository::class);
        $this->app->bind(UserDeviceRepository::class);
        $this->app->bind(UserReferralRepository::class);
    }

    private function registerApplicationService(): void
    {
        $this->app->bind(AuthService::class);
        $this->app->bind(UserService::class);
        $this->app->bind(TelegramService::class);
    }

    private function definedGate(): void
    {
        Gate::define(GatePermission::IS_ADMIN_SYSTEM, function (User $user) {
            return in_array($user->role, [
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ]);
        });

        Gate::define(GatePermission::IS_CUSTOMER, function (User $user) {
            return in_array($user->role, [
                UserRole::AGENCY->value,
                UserRole::CUSTOMER->value,
            ]);
        });
    }
}
