<?php

namespace App\Providers;

use App\Common\Constants\User\GatePermission;
use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Repositories\ConfigRepository;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\GoogleAdsAccountInsightRepository;
use App\Repositories\GoogleAdsCampaignRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserDeviceRepository;
use App\Repositories\PlatformSettingRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\WalletRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Service\AuthService;
use App\Service\GoogleAdsService;
use App\Service\MailService;
use App\Service\MetaBusinessService;
use App\Service\MetaService;
use App\Service\ServicePackageService;
use App\Service\ServicePurchaseService;
use App\Service\ServiceUserService;
use App\Service\BinanceService;
use App\Service\NowPaymentsService;
use App\Service\ConfigService;
use App\Service\TelegramService;
use App\Service\UserService;
use App\Service\PlatformSettingService;
use App\Service\WalletService;
use App\Service\WalletTransactionService;
use App\Service\NotificationService;
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

    /**
     * ------ Đăng ký service ------
     */

    /**
     * Đăng ký repository
     * @return void
     */
    private function registerRepository(): void
    {
        $this->app->bind(ConfigRepository::class);
        $this->app->bind(UserRepository::class);
        $this->app->bind(UserOtpRepository::class);
        $this->app->bind(UserDeviceRepository::class);
        $this->app->bind(UserReferralRepository::class);
        $this->app->bind(PlatformSettingRepository::class);
        $this->app->bind(WalletRepository::class);
        $this->app->bind(UserWalletTransactionRepository::class);
        $this->app->bind(NotificationRepository::class);
        $this->app->bind(ServicePackageRepository::class);
        $this->app->bind(ServiceUserRepository::class);
        $this->app->bind(MetaAccountRepository::class);
        $this->app->bind(MetaAdsCampaignRepository::class);
        $this->app->bind(MetaAdsAccountInsightRepository::class);
        $this->app->bind(GoogleAccountRepository::class);
        $this->app->bind(GoogleAdsAccountInsightRepository::class);
        $this->app->bind(GoogleAdsCampaignRepository::class);
    }

    /**
     * Đăng ký service
     * @return void
     */
    private function registerApplicationService(): void
    {
        $this->app->bind(AuthService::class);
        $this->app->bind(BinanceService::class);
        $this->app->bind(NowPaymentsService::class);
        $this->app->bind(ConfigService::class);
        $this->app->bind(UserService::class);
        $this->app->bind(TelegramService::class);
        $this->app->bind(PlatformSettingService::class);
        $this->app->bind(WalletService::class);
        $this->app->bind(WalletTransactionService::class);
        $this->app->bind(NotificationService::class);
        $this->app->bind(MailService::class);
        $this->app->bind(ServicePackageService::class);
        $this->app->bind(ServicePurchaseService::class);
        $this->app->bind(ServiceUserService::class);
        $this->app->singleton(MetaBusinessService::class);
        $this->app->bind(MetaService::class);
        $this->app->bind(GoogleAdsService::class);
    }

     /**
     * ------ Boot service ------
     */

    /**
     * Định nghĩa gate
     * @return void
     */
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
