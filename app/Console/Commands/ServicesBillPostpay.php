<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Common\Constants\Google\GoogleCampaignStatus;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\Config\ConfigName;
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
use App\Service\ConfigService;
use App\Service\MetaService;
use App\Service\GoogleAdsService;
use Carbon\Carbon;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ServicesBillPostpay extends Command
{
    protected $signature = 'services:bill-postpay';
    protected $description = 'Tính phí spending trả sau khi chi tiêu mới đạt ngưỡng 20 USD và tự động pause campaign khi ví dưới 20 USD';

    private const SPENDING_FEE_CHARGE_THRESHOLD = 20.0;
    private const MIN_WALLET_BALANCE = 20.0;

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
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    /**
     * Ngưỡng ví tối thiểu cho trả sau, lấy từ cấu hình (fallback 20 USD).
     */
    private function minWalletBalance(): float
    {
        $value = $this->configService->getValue(ConfigName::POSTPAY_MIN_BALANCE);

        return is_numeric($value) ? (float) $value : self::MIN_WALLET_BALANCE;
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

                            $currentConfig = $locked->config_account ?? [];
                            if (!is_array($currentConfig)) {
                                $currentConfig = [];
                            }

                            $spendingData = $this->calculateSpendingAndUnbilled($locked, $currentConfig);
                            $spending = $spendingData['total_spend'];
                            $billedSpend = $spendingData['billed_spend'];
                            $unbilledSpend = $spendingData['unbilled_spend'];

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

                            $minWalletBalance = $this->minWalletBalance();
                            $currentBalance = (float) $wallet->balance;

                            // ── TRƯỜNG HỢP 1: Số dư ví < Ngưỡng duy trì tối thiểu (20 USD) ──
                            // Khách hàng đang vi phạm số dư -> CƯỠNG CHẾ PAUSE TẤT CẢ CAMPAIGN ACTIVE NGAY LẬP TỨC!
                            if ($currentBalance < $minWalletBalance) {
                                $pauseStats = $this->pauseAllCampaignsForServiceUser($locked);
                                if ($pauseStats['total'] > 0) {
                                    Logging::web('services:bill-postpay low balance auto-paused active campaigns', [
                                        'service_user_id' => $locked->id,
                                        'user_id' => $locked->user_id,
                                        'balance' => $currentBalance,
                                        'minimum_wallet_balance' => $minWalletBalance,
                                        'pause_stats' => $pauseStats,
                                    ]);
                                }

                                if ($unbilledSpend > 0) {
                                    $spendingFee = $unbilledSpend * ($feePercent / 100);
                                    $chargeAmount = round($spendingFee, 2);

                                    // Nếu ví còn đủ tiền để trừ khoản phí nợ này thì trừ phí và cập nhật mốc đã thu
                                    if ($chargeAmount > 0 && $currentBalance >= $chargeAmount) {
                                        $wallet->update(['balance' => $currentBalance - $chargeAmount]);

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
                                                'accounts_detail' => $spendingData['accounts_detail'] ?? [],
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

                                        $currentConfig['spending_fee_accounts_billed_spend'] = $spendingData['new_accounts_billed_spend'];
                                        $currentConfig['spending_fee_billed_spend'] = $spending;
                                        $currentConfig['spending_fee_last_charged_at'] = now()->toDateTimeString();
                                        $locked->config_account = $currentConfig;
                                        $locked->last_postpay_billed_at = now();
                                        $locked->save();
                                    } else {
                                        // Thiếu tiền trả phí
                                        $this->walletTransactionService->notifySupportGroupPostpayInsufficientBalance(
                                            $locked,
                                            $currentBalance,
                                            $minWalletBalance,
                                            $chargeAmount,
                                            $unbilledSpend,
                                            $pauseStats,
                                        );
                                    }
                                } else {
                                    // unbilled = 0 nhưng ví < 20$, gửi thông báo duy trì số dư
                                    $this->walletTransactionService->notifySupportGroupPostpayLowBalance(
                                        $locked,
                                        $currentBalance,
                                        $minWalletBalance,
                                        $pauseStats,
                                    );
                                }

                                return 'skip';
                            }

                            // ── TRƯỜNG HỢP 2: Số dư ví >= 20 USD ──
                            // Chỉ thu phí khi chi tiêu chưa thu >= 20 USD
                            if ($unbilledSpend < self::SPENDING_FEE_CHARGE_THRESHOLD) {
                                return 'skip';
                            }

                            $spendingFee = $unbilledSpend * ($feePercent / 100);
                            $chargeAmount = round($spendingFee, 2);
                            if ($chargeAmount <= 0) {
                                return 'skip';
                            }

                            // Nếu số dư ví không đủ để thanh toán khoản phí cần thu
                            if ($currentBalance < $chargeAmount) {
                                Logging::web('services:bill-postpay insufficient balance to charge fee, pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'balance' => $wallet->balance,
                                    'unbilled_spend' => $unbilledSpend,
                                    'spending_fee' => $chargeAmount,
                                    'minimum_wallet_balance' => $minWalletBalance,
                                    'charge_amount' => $chargeAmount,
                                ]);

                                $pauseStats = $this->pauseAllCampaignsForServiceUser($locked);

                                $this->walletTransactionService->notifySupportGroupPostpayInsufficientBalance(
                                    $locked,
                                    $currentBalance,
                                    $minWalletBalance,
                                    $chargeAmount,
                                    $unbilledSpend,
                                    $pauseStats,
                                );

                                $user = $wallet->user;
                                $userNotifyCacheKey = 'postpay_insufficient_user_notified_' . $locked->id;
                                if ($user && !Caching::getCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, $userNotifyCacheKey)) {
                                    \App\Core\UserLocale::run($user, function () use ($user, $wallet, $chargeAmount, $minWalletBalance) {
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
                                            'min_wallet' => number_format($minWalletBalance, 2),
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

                                    $expireMinutes = max(60, (int) now()->diffInMinutes(now()->endOfDay()) + 60);
                                    Caching::setCache(
                                        CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED,
                                        now()->toDateTimeString(),
                                        $userNotifyCacheKey,
                                        $expireMinutes
                                    );
                                }

                                return 'skip';
                            }

                            // Trừ tiền phí dịch vụ vào ví của khách
                            $wallet->update(['balance' => $currentBalance - $chargeAmount]);

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
                                    'accounts_detail' => $spendingData['accounts_detail'] ?? [],
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

                            $currentConfig['spending_fee_accounts_billed_spend'] = $spendingData['new_accounts_billed_spend'];
                            $currentConfig['spending_fee_billed_spend'] = $spending;
                            $currentConfig['spending_fee_last_charged_at'] = now()->toDateTimeString();
                            $locked->config_account = $currentConfig;
                            $locked->last_postpay_billed_at = now();
                            $locked->save();

                            // Xóa cache cảnh báo thiếu tiền vì đã thu phí thành công
                            Caching::clearCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, 'postpay_insufficient_group_notified_' . $locked->id);
                            Caching::clearCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, 'postpay_insufficient_user_notified_' . $locked->id);

                            // Nếu sau khi trừ phí mà số dư ví còn lại < ngưỡng tối thiểu (20 USD) -> Tự động Pause campaigns và cảnh báo nạp duy trì
                            $remainingBalance = (float) $wallet->balance;
                            if ($remainingBalance < $minWalletBalance) {
                                Logging::web('services:bill-postpay fee charged but balance below minimum, pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'remaining_balance' => $remainingBalance,
                                    'minimum_wallet_balance' => $minWalletBalance,
                                ]);

                                $pauseStats = $this->pauseAllCampaignsForServiceUser($locked);

                                $this->walletTransactionService->notifySupportGroupPostpayLowBalance(
                                    $locked,
                                    $remainingBalance,
                                    $minWalletBalance,
                                    $pauseStats,
                                );
                            }

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

    /**
     * Tính tổng chi tiêu và chi tiêu chưa thu phí (Unbilled Spend) độc lập theo từng tài khoản
     *
     * @return array{
     *     total_spend: float,
     *     billed_spend: float,
     *     unbilled_spend: float,
     *     new_accounts_billed_spend: array<string, float>,
     *     accounts_detail: array<string, array{spent: float, billed: float, unbilled: float, name: string}>
     * }
     */
    public function calculateSpendingAndUnbilled($serviceUser, array $config): array
    {
        $currencyService = app(\App\Service\CurrencyExchangeService::class);
        $zeroDecimal = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];
        $serviceUserId = (string) $serviceUser->id;

        // Lấy map mốc đã thu theo từng tài khoản từ config
        $savedAccountsBilled = $config['spending_fee_accounts_billed_spend'] ?? [];
        if (!is_array($savedAccountsBilled)) {
            $savedAccountsBilled = [];
        }

        $fallbackGlobalBilled = isset($config['spending_fee_billed_spend']) && is_numeric($config['spending_fee_billed_spend'])
            ? max(0.0, (float) $config['spending_fee_billed_spend'])
            : 0.0;

        $hasPerAccountMap = !empty($savedAccountsBilled);

        $totalSpend = 0.0;
        $totalBilledSpend = 0.0;
        $totalUnbilledSpend = 0.0;
        $newAccountsBilledSpend = $savedAccountsBilled;
        $accountsDetail = [];

        // 1. Meta Accounts
        $metaAccounts = DB::table('meta_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('id', 'account_id', 'account_name', 'amount_spent', 'currency')
            ->get();

        foreach ($metaAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $curr = strtoupper($a->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $spentUSD = $currencyService->convert($amt, $curr, 'USD');
            $totalSpend += $spentUSD;

            $key = 'meta_' . ($a->account_id ?? $a->id);
            $altKey = (string) ($a->account_id ?? $a->id);

            if ($hasPerAccountMap) {
                $billedUSD = (float) ($savedAccountsBilled[$key] ?? $savedAccountsBilled[$altKey] ?? 0.0);
            } else {
                // Nếu chưa có per-account map: nếu tổng spend <= global billed, xem như tài khoản đã được bill tới mốc hiện tại
                $billedUSD = min($spentUSD, $fallbackGlobalBilled);
            }

            $unbilledUSD = max(0.0, $spentUSD - $billedUSD);
            $totalBilledSpend += $billedUSD;
            $totalUnbilledSpend += $unbilledUSD;
            $newAccountsBilledSpend[$key] = $spentUSD;

            $accountsDetail[$key] = [
                'name' => $a->account_name ?? $altKey,
                'spent' => $spentUSD,
                'billed' => $billedUSD,
                'unbilled' => $unbilledUSD,
            ];
        }

        // 2. Google Accounts
        $googleAccounts = DB::table('google_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('id', 'account_id', 'account_name', 'amount_spent', 'currency')
            ->get();

        foreach ($googleAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $curr = strtoupper($a->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $spentUSD = $currencyService->convert($amt, $curr, 'USD');
            $totalSpend += $spentUSD;

            $key = 'google_' . ($a->account_id ?? $a->id);
            $altKey = (string) ($a->account_id ?? $a->id);

            if ($hasPerAccountMap) {
                $billedUSD = (float) ($savedAccountsBilled[$key] ?? $savedAccountsBilled[$altKey] ?? 0.0);
            } else {
                $billedUSD = min($spentUSD, $fallbackGlobalBilled);
            }

            $unbilledUSD = max(0.0, $spentUSD - $billedUSD);
            $totalBilledSpend += $billedUSD;
            $totalUnbilledSpend += $unbilledUSD;
            $newAccountsBilledSpend[$key] = $spentUSD;

            $accountsDetail[$key] = [
                'name' => $a->account_name ?? $altKey,
                'spent' => $spentUSD,
                'billed' => $billedUSD,
                'unbilled' => $unbilledUSD,
            ];
        }

        return [
            'total_spend' => $totalSpend,
            'billed_spend' => $totalBilledSpend,
            'unbilled_spend' => $totalUnbilledSpend,
            'new_accounts_billed_spend' => $newAccountsBilledSpend,
            'accounts_detail' => $accountsDetail,
        ];
    }

    private function getSpendingBetween(string $serviceUserId, string $fromDate, string $toDate): float
    {
        $serviceUser = (object) ['id' => $serviceUserId];
        $res = $this->calculateSpendingAndUnbilled($serviceUser, []);
        return $res['total_spend'];
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

        return 0.0;
    }

    /**
     * Pause tất cả campaigns của service_user khi số dư không đủ
     */
    public function pauseAllCampaignsForServiceUser($serviceUser): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $serviceUserId = (string) $serviceUser->id;

            $metaCampaigns = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUserId)
                ->where('status', '!=', 'PAUSED')
                ->where('status', '!=', 'DELETED')
                ->get(['id']);

            $stats['total'] += $metaCampaigns->count();

            $pausedCampaignIds = [];

            foreach ($metaCampaigns as $campaign) {
                $cId = (string) $campaign->campaign_id ?: (string) $campaign->id;
                $result = $this->metaService->updateCampaignStatus(
                    $serviceUserId,
                    (string) $campaign->id,
                    'PAUSED'
                );
                if ($result->isError()) {
                    $stats['failed']++;
                    $errorMsg = $result->getMessage();
                    if (! in_array($errorMsg, $stats['errors'], true)) {
                        $stats['errors'][] = $errorMsg;
                    }
                    Logging::web('ServicesBillPostpay: Failed to pause Meta campaign', [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaign->id,
                        'error' => $errorMsg,
                    ]);
                } else {
                    $stats['success']++;
                    $pausedCampaignIds[] = $cId;
                }
            }

            // Luôn quét trực tiếp Meta API trên tất cả tài khoản để bắt sạch 100% campaign khách mới tạo trên Ads Manager
            $metaAccounts = DB::table('meta_accounts')
                ->where('service_user_id', $serviceUserId)
                ->whereNull('deleted_at')
                ->get();

            $platformSettingService = app(\App\Service\PlatformSettingService::class);
            $metaBusinessService = app(\App\Service\MetaBusinessService::class);

            foreach ($metaAccounts as $account) {
                try {
                    if (!empty($account->business_manager_id)) {
                        $settingResult = $platformSettingService->findByConfigField(
                            \App\Common\Constants\Platform\PlatformType::META->value,
                            'bm_id',
                            (string) $account->business_manager_id
                        );
                        if (! $settingResult->isError() && $settingResult->getData()) {
                            $metaBusinessService->setSettingId((string) $settingResult->getData()->id);
                        }
                    }

                    $apiCampaignsResult = $metaBusinessService->getCampaignsPaginated(
                        $account->account_id,
                        50
                    );

                    if ($apiCampaignsResult->isSuccess()) {
                        $apiCampaigns = $apiCampaignsResult->getData()['data'] ?? [];
                        foreach ($apiCampaigns as $campData) {
                            $cId = (string) ($campData['id'] ?? '');
                            $cStatus = strtoupper($campData['status'] ?? '');
                            if ($cId && $cStatus !== 'PAUSED' && $cStatus !== 'DELETED' && !in_array($cId, $pausedCampaignIds, true)) {
                                $stats['total']++;
                                $pauseRes = $metaBusinessService->updateCampaignStatus($cId, 'PAUSED');
                                if ($pauseRes->isError()) {
                                    $stats['failed']++;
                                    $err = $pauseRes->getMessage();
                                    if (!in_array($err, $stats['errors'], true)) {
                                        $stats['errors'][] = $err;
                                    }
                                } else {
                                    $stats['success']++;
                                    $pausedCampaignIds[] = $cId;

                                    // Cập nhật hoặc lưu lại vào DB
                                    $this->metaAdsCampaignRepository->query()
                                        ->where('campaign_id', $cId)
                                        ->update(['status' => 'PAUSED', 'effective_status' => 'PAUSED']);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Logging::error('ServicesBillPostpay: scan direct Meta API error', [
                        'account_id' => $account->account_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $googleCampaigns = $this->googleAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUserId)
                ->where('status', '!=', GoogleCampaignStatus::PAUSED->value)
                ->where('status', '!=', GoogleCampaignStatus::REMOVED->value)
                ->get(['id']);

            $stats['total'] += $googleCampaigns->count();

            foreach ($googleCampaigns as $campaign) {
                $result = $this->googleAdsService->updateCampaignStatus(
                    $serviceUserId,
                    (string) $campaign->id,
                    GoogleCampaignStatus::PAUSED->value
                );
                if ($result->isError()) {
                    $stats['failed']++;
                    $errorMsg = $result->getMessage();
                    if (! in_array($errorMsg, $stats['errors'], true)) {
                        $stats['errors'][] = $errorMsg;
                    }
                    Logging::web('ServicesBillPostpay: Failed to pause Google campaign', [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaign->id,
                        'error' => $errorMsg,
                    ]);
                } else {
                    $stats['success']++;
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

        return $stats;
    }
}
