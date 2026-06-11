<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Common\Constants\Google\GoogleCampaignStatus;
use App\Core\Logging;
use App\Models\ServiceUserTransactionLog;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\GoogleAdsCampaignRepository;
use App\Service\TelegramService;
use App\Service\MailService;
use App\Service\MetaService;
use App\Service\GoogleAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ServicesBillPostpay extends Command
{
    protected $signature = 'services:bill-postpay';
    protected $description = 'Tính phí spending trả sau khi chi tiêu mới đạt ngưỡng 100 USD';

    private const SPENDING_FEE_CHARGE_THRESHOLD = 100.0;
    private const MIN_WALLET_BALANCE = 100.0;

    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected WalletRepository $walletRepository,
        protected UserWalletTransactionRepository $walletTransactionRepository,
        protected MetaAdsCampaignRepository $metaAdsCampaignRepository,
        protected GoogleAdsCampaignRepository $googleAdsCampaignRepository,
        protected TelegramService $telegramService,
        protected MailService $mailService,
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();

        $this->serviceUserRepository->query()
            ->with('package')
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->whereHas('package', function ($query) {
                $query->where('spending_fee', '>', 0);
            })
            ->chunkById(100, function ($serviceUsers) use ($today) {
                foreach ($serviceUsers as $serviceUser) {
                    try {
                        $config = $serviceUser->config_account ?? [];
                        $package = $serviceUser->package;
                        if (!$package) {
                            continue;
                        }

                        $feePercent = (float) ($package->spending_fee ?? 0);
                        if ($feePercent <= 0 || !$this->shouldBillSpendingFee($serviceUser, $config)) {
                            continue;
                        }

                        $spending = $this->getSpendingBetween(
                            (string) $serviceUser->id,
                            $serviceUser->created_at->toDateString(),
                            $today->toDateString()
                        );
                        $billedSpend = $this->resolveBilledSpend($serviceUser, $config);
                        $unbilledSpend = max(0.0, $spending - $billedSpend);

                        if ($unbilledSpend < self::SPENDING_FEE_CHARGE_THRESHOLD) {
                            continue;
                        }

                        $spendingFee = $unbilledSpend * ($feePercent / 100);
                        $chargeAmount = round($spendingFee, 2);
                        if ($chargeAmount <= 0) {
                            continue;
                        }

                        $wallet = $this->walletRepository->findByUserId((string) $serviceUser->user_id);
                        if (!$wallet) {
                            Logging::web('services:bill-postpay wallet not found', [
                                'service_user_id' => $serviceUser->id,
                                'user_id' => $serviceUser->user_id,
                            ]);
                            $serviceUser->last_postpay_billed_at = $today;
                            $serviceUser->save();
                            continue;
                        }

                        $requiredWalletBalance = max($chargeAmount, self::MIN_WALLET_BALANCE);
                        if ((float) $wallet->balance < $requiredWalletBalance) {
                            // Số dư không đủ: cảnh báo, pause campaign, và bỏ qua (không cập nhật last_postpay_billed_at để lần sau thử lại)
                            Logging::web('services:bill-postpay insufficient balance, pause campaigns', [
                                'service_user_id' => $serviceUser->id,
                                'user_id' => $serviceUser->user_id,
                                'balance' => $wallet->balance,
                                'unbilled_spend' => $unbilledSpend,
                                'spending_fee' => $chargeAmount,
                                'minimum_wallet_balance' => self::MIN_WALLET_BALANCE,
                                'charge_amount' => $chargeAmount,
                            ]);

                            // Pause tất cả campaigns của service_user này
                            $this->pauseAllCampaignsForServiceUser($serviceUser);

                            // Gửi thông báo cho khách (Telegram hoặc email)
                            $user = $wallet->user;
                            if ($user) {
                                $shortName = $user->name ?? $user->username ?? 'Customer';
                                $balanceFormatted = number_format((float) $wallet->balance, 2);
                                $chargeFormatted = number_format($chargeAmount, 2);
                                $spendingFeeFormatted = number_format($chargeAmount, 2);
                                $message = __('wallet.postpay_charge_insufficient', [
                                    'name' => $shortName,
                                    'balance' => $balanceFormatted,
                                    'charge' => $chargeFormatted,
                                    'monthly_fee' => $spendingFeeFormatted,
                                    'open_fee' => number_format(0, 2),
                                    'min_wallet' => number_format(self::MIN_WALLET_BALANCE, 2),
                                ]);

                                if (!empty($user->telegram_id)) {
                                    $this->telegramService->sendNotification($user->telegram_id, $message);
                                } elseif (!empty($user->email) && !empty($user->email_verified_at)) {
                                    $this->mailService->sendWalletTransactionAlert(
                                        email: $user->email,
                                        username: $shortName,
                                        typeLabel: __('wallet.postpay_charge_label'),
                                        amount: $chargeAmount,
                                        description: $message,
                                    );
                                }
                            }

                            continue;
                        }

                        DB::transaction(function () use ($wallet, $chargeAmount, $package, $serviceUser, $today, $config, $feePercent, $spending, $billedSpend, $unbilledSpend) {
                            $wallet->update(['balance' => (float) $wallet->balance - $chargeAmount]);

                            $walletTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => -$chargeAmount,
                                'type' => WalletTransactionType::SPENDING_FEE->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => "Postpay spending fee ({$feePercent}% on {$unbilledSpend} USD spend): {$package->name}",
                                'reference_id' => (string) $serviceUser->id,
                                'withdraw_info' => [
                                    'purpose' => 'spending_fee',
                                    'spend_amount' => $unbilledSpend,
                                    'spending_fee_percent' => $feePercent,
                                    'spending_fee_amount' => $chargeAmount,
                                    'billed_spend_before' => $billedSpend,
                                    'billed_spend_after' => $spending,
                                    'threshold' => self::SPENDING_FEE_CHARGE_THRESHOLD,
                                ],
                            ]);

                            ServiceUserTransactionLog::create([
                                'service_user_id' => $serviceUser->id,
                                'amount' => $chargeAmount,
                                'type' => ServiceUserTransactionType::FEE->value,
                                'status' => ServiceUserTransactionStatus::COMPLETED->value,
                                'reference_id' => (string) $walletTransaction->id,
                                'description' => "Postpay spending fee ({$feePercent}% on {$unbilledSpend} USD spend): {$package->name}",
                            ]);

                            $config['spending_fee_billed_spend'] = $spending;
                            $config['spending_fee_last_charged_at'] = now()->toDateTimeString();
                            $serviceUser->config_account = $config;
                            $serviceUser->last_postpay_billed_at = now();
                            $serviceUser->save();
                        });
                    } catch (\Throwable $e) {
                        Logging::error(
                            message: 'services:bill-postpay error',
                            context: [
                                'service_user_id' => $serviceUser->id,
                                'user_id' => $serviceUser->user_id,
                                'error' => $e->getMessage(),
                            ],
                            exception: $e
                        );
                    }
                }
            });

        return Command::SUCCESS;
    }

    private function getSpendingBetween(string $serviceUserId, string $fromDate, string $toDate): float
    {
        // Meta spend
        $metaSpend = (float) DB::table('meta_ads_account_insights')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        // Google spend
        $googleSpend = (float) DB::table('google_ads_account_insights')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        return $metaSpend + $googleSpend;
    }

    private function shouldBillSpendingFee($serviceUser, array $config): bool
    {
        $paymentType = $serviceUser->package?->payment_type ?? $config['payment_type'] ?? 'prepay';
        $billingSource = $serviceUser->package?->billing_source ?? $config['billing_source'] ?? null;

        return $paymentType === 'postpay' || $billingSource === 'customer_card';
    }

    private function resolveBilledSpend($serviceUser, array $config): float
    {
        if (isset($config['spending_fee_billed_spend']) && is_numeric($config['spending_fee_billed_spend'])) {
            return max(0.0, (float) $config['spending_fee_billed_spend']);
        }

        if (!$serviceUser->last_postpay_billed_at) {
            return 0.0;
        }

        return $this->getSpendingBetween(
            (string) $serviceUser->id,
            $serviceUser->created_at->toDateString(),
            $serviceUser->last_postpay_billed_at->toDateString()
        );
    }

    /**
     * Pause tất cả campaigns của service_user khi số dư không đủ
     */
    private function pauseAllCampaignsForServiceUser($serviceUser): void
    {
        try {
            $serviceUserId = (string) $serviceUser->id;

            $metaCampaigns = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUserId)
                ->where('status', '!=', 'PAUSED')
                ->where('status', '!=', 'DELETED')
                ->get(['id']);

            foreach ($metaCampaigns as $campaign) {
                $result = $this->metaService->updateCampaignStatus(
                    $serviceUserId,
                    (string) $campaign->id,
                    'PAUSED'
                );
                if ($result->isError()) {
                    Logging::web('ServicesBillPostpay: Failed to pause Meta campaign', [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaign->id,
                        'error' => $result->getMessage(),
                    ]);
                }
            }

            $googleCampaigns = $this->googleAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUserId)
                ->where('status', '!=', GoogleCampaignStatus::PAUSED->value)
                ->where('status', '!=', GoogleCampaignStatus::REMOVED->value)
                ->get(['id']);

            foreach ($googleCampaigns as $campaign) {
                $result = $this->googleAdsService->updateCampaignStatus(
                    $serviceUserId,
                    (string) $campaign->id,
                    GoogleCampaignStatus::PAUSED->value
                );
                if ($result->isError()) {
                    Logging::web('ServicesBillPostpay: Failed to pause Google campaign', [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaign->id,
                        'error' => $result->getMessage(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServicesBillPostpay: Error pausing campaigns',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'error' => $e->getMessage(),
                ],
                exception: $e
            );
        }
    }
}
