<?php

namespace App\Providers;

use App\Common\Constants\User\GatePermission;
use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Service\AuthService;
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
        $this->app->singleton(UserRepository::class);
    }

    private function registerApplicationService(): void
    {
        $this->app->singleton(AuthService::class);
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
