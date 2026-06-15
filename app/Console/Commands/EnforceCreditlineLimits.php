<?php

namespace App\Console\Commands;

use App\Common\Constants\Google\GoogleCampaignStatus;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Models\ServiceUser;
use App\Models\User;
use App\Repositories\GoogleAdsCampaignRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Service\GoogleAdsService;
use App\Service\MailService;
use App\Service\MetaService;
use App\Service\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnforceCreditlineLimits extends Command
{
    protected $signature = 'services:enforce-creditline-limits';

    protected $description = 'Tự động pause campaign khi hạn mức creditline còn dưới ngưỡng an toàn';

    private const PAUSE_REMAINING_CREDIT = 20.0;

    public function __construct(
        protected MetaAdsCampaignRepository $metaAdsCampaignRepository,
        protected GoogleAdsCampaignRepository $googleAdsCampaignRepository,
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $admin = User::query()
            ->where('role', UserRole::ADMIN->value)
            ->orderBy('id')
            ->first();

        if (!$admin) {
            $this->error('Không tìm thấy admin user để chạy job pause campaign.');
            return Command::FAILURE;
        }

        Auth::loginUsingId($admin->id);

        $checked = 0;
        $pausedServices = 0;
        $pausedCampaigns = 0;
        $errors = 0;

        ServiceUser::query()
            ->with(['package', 'user'])
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->whereHas('package', function ($query) {
                $query->where('billing_source', AccountBillingSource::SUPPLIER_CREDIT_LINE->value);
            })
            ->chunkById(100, function ($serviceUsers) use (&$checked, &$pausedServices, &$pausedCampaigns, &$errors) {
                foreach ($serviceUsers as $serviceUser) {
                    $checked++;

                    try {
                        $totalTopUp = $this->calculateTotalTopUp($serviceUser);
                        $totalSpend = $this->calculateTotalSpend((string) $serviceUser->id);
                        $remainingCredit = round($totalTopUp - $totalSpend, 2);

                        if (!$this->shouldPause($totalTopUp, $totalSpend)) {
                            $this->rememberCreditlineState($serviceUser, $totalTopUp, $totalSpend, $remainingCredit, false);
                            continue;
                        }

                        $wasPaused = $this->wasCreditlinePaused($serviceUser);
                        $pausedCount = $this->pauseAllCampaignsForServiceUser($serviceUser);
                        $pausedCampaigns += $pausedCount;
                        $pausedServices++;

                        $this->rememberCreditlineState($serviceUser, $totalTopUp, $totalSpend, $remainingCredit, true);
                        if (!$wasPaused || $pausedCount > 0) {
                            $this->notifyCustomer($serviceUser, $totalTopUp, $totalSpend, $remainingCredit);
                        }

                        Logging::web('services:enforce-creditline-limits paused service campaigns', [
                            'service_user_id' => (string) $serviceUser->id,
                            'user_id' => (string) $serviceUser->user_id,
                            'package_id' => (string) $serviceUser->package_id,
                            'total_top_up' => $totalTopUp,
                            'total_spend' => $totalSpend,
                            'remaining_credit' => $remainingCredit,
                            'campaigns_paused' => $pausedCount,
                        ]);
                    } catch (\Throwable $exception) {
                        $errors++;
                        Logging::error(
                            message: 'services:enforce-creditline-limits error',
                            context: [
                                'service_user_id' => (string) $serviceUser->id,
                                'error' => $exception->getMessage(),
                            ],
                            exception: $exception,
                        );
                    }
                }
            });

        $this->info(sprintf(
            'Đã kiểm tra %d dịch vụ creditline, pause %d dịch vụ, %d campaign, %d lỗi.',
            $checked,
            $pausedServices,
            $pausedCampaigns,
            $errors,
        ));

        return Command::SUCCESS;
    }

    private function calculateTotalTopUp(ServiceUser $serviceUser): float
    {
        $config = is_array($serviceUser->config_account) ? $serviceUser->config_account : [];
        $initialTopUp = isset($config['top_up_amount']) && is_numeric($config['top_up_amount'])
            ? max(0.0, (float) $config['top_up_amount'])
            : 0.0;

        $additionalTopUp = DB::table('user_wallet_transactions')
            ->whereNull('deleted_at')
            ->whereIn('type', [
                WalletTransactionType::ACCOUNT_TOP_UP_GOOGLE->value,
                WalletTransactionType::ACCOUNT_TOP_UP_META->value,
            ])
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->where('withdraw_info->service_user_id', (string) $serviceUser->id)
            ->get(['withdraw_info'])
            ->sum(function ($transaction) {
                $metadata = json_decode((string) $transaction->withdraw_info, true);
                if (!is_array($metadata)) {
                    return 0.0;
                }

                return isset($metadata['top_up_amount']) && is_numeric($metadata['top_up_amount'])
                    ? max(0.0, (float) $metadata['top_up_amount'])
                    : 0.0;
            });

        return round($initialTopUp + (float) $additionalTopUp, 2);
    }

    private function calculateTotalSpend(string $serviceUserId): float
    {
        $metaSpend = (float) DB::table('meta_ads_account_insights')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        $googleSpend = (float) DB::table('google_ads_account_insights')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        return round($metaSpend + $googleSpend, 2);
    }

    private function shouldPause(float $totalTopUp, float $totalSpend): bool
    {
        if ($totalTopUp <= 0) {
            return $totalSpend > 0;
        }

        return ($totalTopUp - $totalSpend) <= self::PAUSE_REMAINING_CREDIT;
    }

    private function wasCreditlinePaused(ServiceUser $serviceUser): bool
    {
        $config = is_array($serviceUser->config_account) ? $serviceUser->config_account : [];
        return (bool) data_get($config, 'creditline_limit.paused', false);
    }

    private function pauseAllCampaignsForServiceUser(ServiceUser $serviceUser): int
    {
        $serviceUserId = (string) $serviceUser->id;
        $paused = 0;

        $metaCampaigns = $this->metaAdsCampaignRepository->query()
            ->where('service_user_id', $serviceUserId)
            ->where('status', '!=', 'PAUSED')
            ->where('status', '!=', 'DELETED')
            ->get(['id']);

        foreach ($metaCampaigns as $campaign) {
            $result = $this->metaService->updateCampaignStatus($serviceUserId, (string) $campaign->id, 'PAUSED');
            if ($result->isSuccess()) {
                $paused++;
                continue;
            }

            Logging::web('services:enforce-creditline-limits failed to pause Meta campaign', [
                'service_user_id' => $serviceUserId,
                'campaign_id' => (string) $campaign->id,
                'error' => $result->getMessage(),
            ]);
        }

        $googleCampaigns = $this->googleAdsCampaignRepository->query()
            ->where('service_user_id', $serviceUserId)
            ->where('status', '!=', GoogleCampaignStatus::PAUSED->value)
            ->where('status', '!=', GoogleCampaignStatus::REMOVED->value)
            ->get(['id']);

        foreach ($googleCampaigns as $campaign) {
            $result = $this->googleAdsService->updateCampaignStatus($serviceUserId, (string) $campaign->id, GoogleCampaignStatus::PAUSED->value);
            if ($result->isSuccess()) {
                $paused++;
                continue;
            }

            Logging::web('services:enforce-creditline-limits failed to pause Google campaign', [
                'service_user_id' => $serviceUserId,
                'campaign_id' => (string) $campaign->id,
                'error' => $result->getMessage(),
            ]);
        }

        return $paused;
    }

    private function rememberCreditlineState(ServiceUser $serviceUser, float $totalTopUp, float $totalSpend, float $remainingCredit, bool $paused): void
    {
        $config = is_array($serviceUser->config_account) ? $serviceUser->config_account : [];
        $config['creditline_limit'] = [
            'total_top_up' => $totalTopUp,
            'total_spend' => $totalSpend,
            'remaining_credit' => $remainingCredit,
            'pause_remaining_credit' => self::PAUSE_REMAINING_CREDIT,
            'paused' => $paused,
            'checked_at' => now()->toDateTimeString(),
        ];

        $serviceUser->config_account = $config;
        $serviceUser->save();
    }

    private function notifyCustomer(ServiceUser $serviceUser, float $totalTopUp, float $totalSpend, float $remainingCredit): void
    {
        $user = $serviceUser->user;
        if (!$user) {
            return;
        }

        $name = $user->name ?? $user->username ?? 'Customer';
        $message = sprintf(
            'Xin chào %s, dịch vụ %s đã chạm ngưỡng an toàn creditline. Tổng top up: %s USDT, tổng spend: %s USD, còn lại: %s. Campaign đã được tạm dừng khi hạn mức còn khoảng %s USD, vui lòng top up thêm để tiếp tục chạy.',
            $name,
            $serviceUser->package?->name ?? (string) $serviceUser->id,
            number_format($totalTopUp, 2),
            number_format($totalSpend, 2),
            number_format($remainingCredit, 2),
            number_format(self::PAUSE_REMAINING_CREDIT, 2),
        );

        if (!empty($user->telegram_id)) {
            $this->telegramService->sendNotification($user->telegram_id, $message);
            return;
        }

        if (!empty($user->email) && !empty($user->email_verified_at)) {
            $this->mailService->sendWalletTransactionAlert(
                email: $user->email,
                username: $name,
                typeLabel: 'Creditline limit',
                amount: max(0.0, abs($remainingCredit)),
                description: $message,
            );
        }
    }
}
