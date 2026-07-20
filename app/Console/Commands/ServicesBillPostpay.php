<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Common\Constants\Google\GoogleCampaignStatus;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Core\Logging;
use App\Models\ServiceUserTransactionLog;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\GoogleAdsCampaignRepository;
use App\Service\TelegramService;
use App\Service\WalletTransactionService;
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
        protected WalletTransactionService $walletTransactionService,
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
                $query->where('spending_fee', '>', 0)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('billing_source', AccountBillingSource::CUSTOMER_CARD->value)
                            ->where('top_up_fee', '>', 0);
                    });
            })
            ->chunkById(100, function ($serviceUsers) use ($today) {
                foreach ($serviceUsers as $serviceUser) {
                    try {
                        $config = $serviceUser->config_account ?? [];
                        $package = $serviceUser->package;
                        if (!$package) {
                            continue;
                        }

                        $feePercent = $this->resolveSpendingFeePercent($package, $config);
                        if ($feePercent <= 0 || !$this->shouldBillSpendingFee($serviceUser, $config)) {
                            continue;
                        }

                        // Lock row to prevent duplicate charges from concurrent cron runs
                        $lockedUser = DB::transaction(function () use ($serviceUser, $today, $feePercent, $package, $config) {
                            $locked = $this->serviceUserRepository->query()
                                ->where('id', $serviceUser->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$locked) {
                                return null;
                            }

                            $spending = $this->getSpendingBetween(
                                (string) $locked->id,
                                $locked->created_at->toDateString(),
                                $today->toDateString()
                            );
                            $billedSpend = $this->resolveBilledSpend($locked, $config);
                            $unbilledSpend = max(0.0, $spending - $billedSpend);

                            if ($unbilledSpend < self::SPENDING_FEE_CHARGE_THRESHOLD) {
                                return 'skip';
                            }

                            $spendingFee = $unbilledSpend * ($feePercent / 100);
                            $chargeAmount = round($spendingFee, 2);
                            if ($chargeAmount <= 0) {
                                return 'skip';
                            }

                            $wallet = $this->walletRepository->findByUserId((string) $locked->user_id);
                            if (!$wallet) {
                                Logging::web('services:bill-postpay wallet not found', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                ]);
                                $locked->last_postpay_billed_at = $today;
                                $locked->save();
                                return 'skip';
                            }

                            $requiredWalletBalance = max($chargeAmount, self::MIN_WALLET_BALANCE);
                            if ((float) $wallet->balance < $requiredWalletBalance) {
                                Logging::web('services:bill-postpay insufficient balance, pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'balance' => $wallet->balance,
                                    'unbilled_spend' => $unbilledSpend,
                                    'spending_fee' => $chargeAmount,
                                    'minimum_wallet_balance' => self::MIN_WALLET_BALANCE,
                                    'charge_amount' => $chargeAmount,
                                ]);

                                $this->pauseAllCampaignsForServiceUser($locked);

                                $user = $wallet->user;
                                if ($user) {
                                    \App\Core\UserLocale::run($user, function () use ($user, $wallet, $chargeAmount) {
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
                                    });
                                }

                                return 'skip';
                            }

                            $wallet->update(['balance' => (float) $wallet->balance - $chargeAmount]);

                            $walletTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => -$chargeAmount,
                                'type' => WalletTransactionType::SPENDING_FEE->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => "Postpay spending fee ({$feePercent}% on {$unbilledSpend} USD spend from {$billedSpend} to {$spending}): {$package->name}",
                                'reference_id' => (string) $locked->id,
                                'withdraw_info' => [
                                    'purpose' => 'spending_fee',
                                    'spend_amount' => $unbilledSpend,
                                    'spending_fee_percent' => $feePercent,
                                    'spending_fee_amount' => $chargeAmount,
                                    'billed_spend_before' => $billedSpend,
                                    'billed_spend_after' => $spending,
                                    'threshold' => self::SPENDING_FEE_CHARGE_THRESHOLD,
                                    'last_billed_at' => $locked->last_postpay_billed_at?->toDateTimeString() ?? null,
                                    'charged_at' => now()->toDateTimeString(),
                                ],
                            ]);

                            ServiceUserTransactionLog::create([
                                'service_user_id' => $locked->id,
                                'amount' => $chargeAmount,
                                'type' => ServiceUserTransactionType::FEE->value,
                                'status' => ServiceUserTransactionStatus::COMPLETED->value,
                                'reference_id' => (string) $walletTransaction->id,
                                'description' => "Postpay spending fee ({$feePercent}% on {$unbilledSpend} USD spend from {$billedSpend} to {$spending}): {$package->name}",
                            ]);

                            $this->walletTransactionService->notifySupportGroupSpendingFee(
                                $walletTransaction,
                                $package->name,
                                $unbilledSpend,
                                $chargeAmount,
                            );

                            $config['spending_fee_billed_spend'] = $spending;
                            $config['spending_fee_last_charged_at'] = now()->toDateTimeString();
                            $locked->config_account = $config;
                            $locked->last_postpay_billed_at = now();
                            $locked->save();

                            return $locked;
                        });

                        if ($lockedUser === null || $lockedUser === 'skip') {
                            continue;
                        }
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
        $currencyService = app(\App\Service\CurrencyExchangeService::class);

        // Meta spend: convert từng account theo currency → USD
        $metaAccounts = DB::table('meta_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('amount_spent', 'currency')
            ->get();

        $metaSpend = 0.0;
        foreach ($metaAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $currency = strtoupper($a->currency ?? 'USD');
            // Zero-decimal currencies: VND, JPY, KRW, etc. → không chia 100
            $zeroDecimal = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
            $amount = in_array($currency, $zeroDecimal) ? $raw : $raw / 100;
            // Convert về USD
            $metaSpend += $currencyService->convert($amount, $currency, 'USD');
        }

        // Google spend
        $googleAccounts = DB::table('google_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('amount_spent', 'currency')
            ->get();

        $googleSpend = 0.0;
        foreach ($googleAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $currency = strtoupper($a->currency ?? 'USD');
            $zeroDecimal = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
            $amount = in_array($currency, $zeroDecimal) ? $raw : $raw / 100;
            $googleSpend += $currencyService->convert($amount, $currency, 'USD');
        }

        return $metaSpend + $googleSpend;
    }

    private function shouldBillSpendingFee($serviceUser, array $config): bool
    {
        $paymentType = $serviceUser->package?->payment_type ?? $config['payment_type'] ?? 'prepay';
        $billingSource = $serviceUser->package?->billing_source ?? $config['billing_source'] ?? null;

        return $paymentType === 'postpay' || $billingSource === 'customer_card';
    }

    private function resolveSpendingFeePercent($package, array $config): float
    {
        $spendingFee = (float) ($package?->spending_fee ?? 0);
        if ($spendingFee > 0) {
            return $spendingFee;
        }

        $billingSource = $package?->billing_source ?? $config['billing_source'] ?? null;
        if ($billingSource === AccountBillingSource::CUSTOMER_CARD->value) {
            return (float) ($package?->top_up_fee ?? 0);
        }

        return 0.0;
    }

    private function resolveBilledSpend($serviceUser, array $config): float
    {
        // Ưu tiên billed_spend đã lưu trong config (tích lũy, chính xác nhất)
        if (isset($config['spending_fee_billed_spend']) && is_numeric($config['spending_fee_billed_spend'])) {
            return max(0.0, (float) $config['spending_fee_billed_spend']);
        }

        // Chưa bill lần nào → billed = 0
        return 0.0;
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
