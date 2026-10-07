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

                            $currentBalance = (float) $wallet->balance;

                            // 1. Nếu số dư ví <= 0 (hết sạch tiền) -> Tự động pause campaigns và dừng
                            if ($currentBalance <= 0) {
                                Logging::web('services:bill-postpay wallet balance depleted (<= 0), pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'balance' => $currentBalance,
                                ]);
                                $this->pauseAllCampaignsForServiceUser($locked);
                                return 'skip';
                            }

                            // 2. Kiểm tra chi tiêu chưa thu phí
                            // Chỉ thu phí khi chi tiêu chưa thu >= 20 USD (ngưỡng tối thiểu để tính phí)
                            if ($unbilledSpend < self::SPENDING_FEE_CHARGE_THRESHOLD) {
                                return 'skip';
                            }

                            $spendingFee = $unbilledSpend * ($feePercent / 100);
                            $chargeAmount = round($spendingFee, 2);
                            if ($chargeAmount <= 0) {
                                return 'skip';
                            }

                            // 3. Nếu số dư ví không đủ để thanh toán khoản phí cần thu ($currentBalance < $chargeAmount)
                            if ($currentBalance < $chargeAmount) {
                                Logging::web('services:bill-postpay insufficient balance to charge fee, pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'balance' => $wallet->balance,
                                    'unbilled_spend' => $unbilledSpend,
                                    'spending_fee' => $chargeAmount,
                                    'charge_amount' => $chargeAmount,
                                ]);

                                $pauseStats = $this->pauseAllCampaignsForServiceUser($locked);

                                $this->walletTransactionService->notifySupportGroupPostpayInsufficientBalance(
                                    $locked,
                                    $currentBalance,
                                    $chargeAmount,
                                    $chargeAmount,
                                    $unbilledSpend,
                                    $pauseStats,
                                    $spendingData['accounts_detail'] ?? [],
                                );

                                $user = $wallet->user;
                                $userNotifyCacheKey = 'postpay_insufficient_user_notified_' . $locked->id;
                                if ($user && !Caching::getCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, $userNotifyCacheKey)) {
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
                                            'min_wallet' => number_format($chargeAmount, 2),
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

                            // 4. Số dư ví ĐỦ để trừ phí ($currentBalance >= $chargeAmount): Trừ phí và cho khách chạy tiếp!
                            $wallet->update(['balance' => $currentBalance - $chargeAmount]);

                            $description = $this->buildSpendingFeeDescription(
                                feePercent: $feePercent,
                                unbilledSpend: $unbilledSpend,
                                billedSpend: $billedSpend,
                                spending: $spending,
                                packageName: $package->name,
                                accountsDetail: $spendingData['accounts_detail'] ?? [],
                            );

                            $walletTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => -$chargeAmount,
                                'type' => WalletTransactionType::SPENDING_FEE->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => $description,
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
                                'description' => $description,
                            ]);

                            $currentConfig['spending_fee_accounts_billed_spend'] = $spendingData['new_accounts_billed_spend'];
                            $currentConfig['spending_fee_billed_spend'] = $spending;
                            $currentConfig['spending_fee_last_charged_at'] = now()->toDateTimeString();
                            $locked->config_account = $currentConfig;
                            $locked->last_postpay_billed_at = now();
                            $locked->save();

                            try {
                                $this->walletTransactionService->notifySupportGroupSpendingFee(
                                    $walletTransaction,
                                    $package->name,
                                    $unbilledSpend,
                                    $chargeAmount,
                                    $spendingData['accounts_detail'] ?? [],
                                );
                            } catch (\Throwable $notifEx) {
                                Logging::error('ServicesBillPostpay spending fee notify error: ' . $notifEx->getMessage(), exception: $notifEx);
                            }

                            // Xóa cache cảnh báo thiếu tiền vì đã thu phí thành công
                            Caching::clearCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, 'postpay_insufficient_group_notified_' . $locked->id);
                            Caching::clearCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, 'postpay_insufficient_user_notified_' . $locked->id);

                            // Sau khi trừ phí: CHỈ pause nếu số dư còn lại thực sự <= 0 (hết sạch tiền).
                            // Tuyệt đối KHÔNG pause nếu khách vẫn còn tiền (> 0) để khách chạy hết số dư!
                            $remainingBalance = (float) $wallet->balance;
                            if ($remainingBalance <= 0) {
                                Logging::web('services:bill-postpay fee charged and balance depleted (<= 0), pause campaigns', [
                                    'service_user_id' => $locked->id,
                                    'user_id' => $locked->user_id,
                                    'remaining_balance' => $remainingBalance,
                                ]);
                                $this->pauseAllCampaignsForServiceUser($locked);
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

    public function buildSpendingFeeDescription(
        float $feePercent,
        float $unbilledSpend,
        float $billedSpend,
        float $spending,
        string $packageName,
        array $accountsDetail = [],
    ): string {
        $accountsWithUnbilled = array_filter(
            $accountsDetail,
            fn ($d) => (float) ($d['unbilled'] ?? 0) > 0 || (float) ($d['spent'] ?? 0) > 0
        );

        $accountSummary = '';
        if (count($accountsWithUnbilled) > 1) {
            $accountSummary = ' on ' . count($accountsWithUnbilled) . ' accounts';
        }

        return "Postpay spending fee ({$feePercent}% on {$unbilledSpend} USD spend from {$billedSpend} to {$spending}{$accountSummary}): {$packageName}";
    }

    /**
     * Tính tổng chi tiêu và chi tiêu chưa thu phí (Unbilled Spend) độc lập theo từng tài khoản
     *
     * @return array{
     *     total_spend: float,
     *     billed_spend: float,
     *     unbilled_spend: float,
     *     new_accounts_billed_spend: array<string, float>,
     *     accounts_detail: array<string, array{account_id: string, name: string, spent: float, billed: float, unbilled: float, fee: float}>
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

        // MỐC BẢO VỆ TỐI THƯỢNG: Kiểm tra lịch sử giao dịch SPENDING_FEE đã thành công trong DB (ngăn ngừa tuyệt đối việc bill lặp lại)
        try {
            $lastTxs = DB::table('user_wallet_transactions')
                ->where('reference_id', $serviceUserId)
                ->where('type', WalletTransactionType::SPENDING_FEE->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            foreach ($lastTxs as $tx) {
                $wInfo = !empty($tx->withdraw_info)
                    ? (is_string($tx->withdraw_info) ? json_decode($tx->withdraw_info, true) : (array) $tx->withdraw_info)
                    : [];

                if (is_array($wInfo)) {
                    $txBilledAfter = (float) ($wInfo['billed_spend_after'] ?? 0);
                    if ($txBilledAfter > $fallbackGlobalBilled) {
                        $fallbackGlobalBilled = $txBilledAfter;
                    }

                    if (!empty($wInfo['accounts_detail']) && is_array($wInfo['accounts_detail'])) {
                        foreach ($wInfo['accounts_detail'] as $k => $detail) {
                            $accSpent = (float) ($detail['spent'] ?? 0);
                            $cleanK = preg_replace('/^act_/', '', (string) $k);
                            if ($accSpent > 0) {
                                if (!isset($savedAccountsBilled[$k]) || (float) $savedAccountsBilled[$k] < $accSpent) {
                                    $savedAccountsBilled[$k] = $accSpent;
                                }
                                if (!isset($savedAccountsBilled[$cleanK]) || (float) $savedAccountsBilled[$cleanK] < $accSpent) {
                                    $savedAccountsBilled[$cleanK] = $accSpent;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Không để lỗi query ảnh hưởng tính toán
        }

        $hasPerAccountMap = !empty($savedAccountsBilled);

        $totalSpend = 0.0;
        $totalBilledSpend = 0.0;
        $totalUnbilledSpend = 0.0;
        $newAccountsBilledSpend = $savedAccountsBilled;
        $accountsDetail = [];

        $feePercent = 0.0;
        if (isset($serviceUser->package)) {
            $feePercent = $this->resolveSpendingFeePercent($serviceUser->package, $config);
        }

        // Trích xuất danh sách account IDs được gán chính thức trong config (nếu có)
        $configuredMetaIds = [];
        $hasExplicitMetaConfig = false;

        if (!empty($config['accounts']) && is_array($config['accounts'])) {
            $hasExplicitMetaConfig = true;
            foreach ($config['accounts'] as $acc) {
                $id = $acc['account_id'] ?? $acc['id'] ?? null;
                if ($id) {
                    $clean = preg_replace('/^act_/', '', trim((string) $id));
                    $configuredMetaIds[$clean] = true;
                    $configuredMetaIds['act_' . $clean] = true;
                }
            }
        } elseif (!empty($config['account_ids']) && is_array($config['account_ids'])) {
            $hasExplicitMetaConfig = true;
            foreach ($config['account_ids'] as $id) {
                if ($id) {
                    $clean = preg_replace('/^act_/', '', trim((string) $id));
                    $configuredMetaIds[$clean] = true;
                    $configuredMetaIds['act_' . $clean] = true;
                }
            }
        } elseif (!empty($config['account_id'])) {
            $hasExplicitMetaConfig = true;
            $clean = preg_replace('/^act_/', '', trim((string) $config['account_id']));
            $configuredMetaIds[$clean] = true;
            $configuredMetaIds['act_' . $clean] = true;
        }

        // 1. Meta Accounts
        $metaAccounts = DB::table('meta_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('id', 'account_id', 'account_name', 'amount_spent', 'currency')
            ->get();

        // Nếu đơn hàng có cấu hình danh sách tài khoản cụ thể, tự động loại bỏ & gỡ gán các tài khoản rác không nằm trong config
        if ($hasExplicitMetaConfig && !empty($configuredMetaIds)) {
            $staleIds = [];
            $validMetaAccounts = [];
            foreach ($metaAccounts as $a) {
                $accId = (string) ($a->account_id ?? $a->id);
                $cleanId = preg_replace('/^act_/', '', $accId);
                if (isset($configuredMetaIds[$cleanId]) || isset($configuredMetaIds[$accId])) {
                    $validMetaAccounts[] = $a;
                } else {
                    $staleIds[] = (string) $a->id;
                }
            }
            if (!empty($staleIds)) {
                DB::table('meta_accounts')->whereIn('id', $staleIds)->update(['service_user_id' => null]);
            }
            $metaAccounts = collect($validMetaAccounts);
        }

        foreach ($metaAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $curr = strtoupper($a->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $spentUSD = $currencyService->convert($amt, $curr, 'USD');

            $accId = (string) ($a->account_id ?? $a->id);
            $cleanId = preg_replace('/^act_/', '', $accId);
            $actId = 'act_' . $cleanId;

            $billedUSD = 0.0;
            if ($hasPerAccountMap) {
                if (isset($savedAccountsBilled['meta_' . $actId])) {
                    $billedUSD = (float) $savedAccountsBilled['meta_' . $actId];
                } elseif (isset($savedAccountsBilled['meta_' . $cleanId])) {
                    $billedUSD = (float) $savedAccountsBilled['meta_' . $cleanId];
                } elseif (isset($savedAccountsBilled[$actId])) {
                    $billedUSD = (float) $savedAccountsBilled[$actId];
                } elseif (isset($savedAccountsBilled[$cleanId])) {
                    $billedUSD = (float) $savedAccountsBilled[$cleanId];
                }
            } else {
                $billedUSD = min($spentUSD, $fallbackGlobalBilled);
            }

            $unbilledUSD = max(0.0, $spentUSD - $billedUSD);
            $totalSpend += $spentUSD;
            $totalBilledSpend += $billedUSD;
            $totalUnbilledSpend += $unbilledUSD;

            $canonicalKey = 'meta_' . $actId;
            $newAccountsBilledSpend[$canonicalKey] = $spentUSD;
            $newAccountsBilledSpend['meta_' . $cleanId] = $spentUSD;

            $accountFee = round($unbilledUSD * ($feePercent / 100), 2);

            $accountsDetail[$canonicalKey] = [
                'account_id' => $actId,
                'name' => $a->account_name ?? $actId,
                'spent' => $spentUSD,
                'billed' => $billedUSD,
                'unbilled' => $unbilledUSD,
                'fee' => $accountFee,
            ];
        }

        // Trích xuất danh sách account IDs Google được gán chính thức trong config (nếu có)
        $configuredGoogleIds = [];
        $hasExplicitGoogleConfig = false;
        if (!empty($config['account_ids']) && is_array($config['account_ids'])) {
            $hasExplicitGoogleConfig = true;
            foreach ($config['account_ids'] as $id) {
                $clean = preg_replace('/[^0-9]/', '', (string) $id);
                if ($clean) $configuredGoogleIds[$clean] = true;
            }
        } elseif (!empty($config['account_id'])) {
            $hasExplicitGoogleConfig = true;
            $clean = preg_replace('/[^0-9]/', '', (string) $config['account_id']);
            if ($clean) $configuredGoogleIds[$clean] = true;
        }

        // 2. Google Accounts
        $googleAccounts = DB::table('google_accounts')
            ->where('service_user_id', $serviceUserId)
            ->whereNull('deleted_at')
            ->select('id', 'account_id', 'account_name', 'amount_spent', 'currency')
            ->get();

        if ($hasExplicitGoogleConfig && !empty($configuredGoogleIds)) {
            $staleIds = [];
            $validGoogleAccounts = [];
            foreach ($googleAccounts as $a) {
                $accId = (string) ($a->account_id ?? $a->id);
                $cleanId = preg_replace('/[^0-9]/', '', $accId);
                if (isset($configuredGoogleIds[$cleanId]) || isset($configuredGoogleIds[$accId])) {
                    $validGoogleAccounts[] = $a;
                } else {
                    $staleIds[] = (string) $a->id;
                }
            }
            if (!empty($staleIds)) {
                DB::table('google_accounts')->whereIn('id', $staleIds)->update(['service_user_id' => null]);
            }
            $googleAccounts = collect($validGoogleAccounts);
        }

        foreach ($googleAccounts as $a) {
            $raw = (float) ($a->amount_spent ?? 0);
            $curr = strtoupper($a->currency ?? 'USD');
            $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
            $spentUSD = $currencyService->convert($amt, $curr, 'USD');

            $accId = (string) ($a->account_id ?? $a->id);
            $cleanId = preg_replace('/[^0-9]/', '', $accId);

            $billedUSD = 0.0;
            if ($hasPerAccountMap) {
                if (isset($savedAccountsBilled['google_' . $cleanId])) {
                    $billedUSD = (float) $savedAccountsBilled['google_' . $cleanId];
                } elseif (isset($savedAccountsBilled['google_' . $accId])) {
                    $billedUSD = (float) $savedAccountsBilled['google_' . $accId];
                } elseif (isset($savedAccountsBilled[$cleanId])) {
                    $billedUSD = (float) $savedAccountsBilled[$cleanId];
                } elseif (isset($savedAccountsBilled[$accId])) {
                    $billedUSD = (float) $savedAccountsBilled[$accId];
                }
            } else {
                $billedUSD = min($spentUSD, $fallbackGlobalBilled);
            }

            $unbilledUSD = max(0.0, $spentUSD - $billedUSD);
            $totalSpend += $spentUSD;
            $totalBilledSpend += $billedUSD;
            $totalUnbilledSpend += $unbilledUSD;

            $canonicalKey = 'google_' . ($cleanId ?: $accId);
            $newAccountsBilledSpend[$canonicalKey] = $spentUSD;
            $newAccountsBilledSpend['google_' . $accId] = $spentUSD;
            if ($cleanId) {
                $newAccountsBilledSpend[$cleanId] = $spentUSD;
            }

            $accountFee = round($unbilledUSD * ($feePercent / 100), 2);

            $accountsDetail[$canonicalKey] = [
                'account_id' => $cleanId ?: $accId,
                'name' => $a->account_name ?? ($cleanId ?: $accId),
                'spent' => $spentUSD,
                'billed' => $billedUSD,
                'unbilled' => $unbilledUSD,
                'fee' => $accountFee,
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
            $platformSettingService = app(\App\Service\PlatformSettingService::class);
            $metaBusinessService = app(\App\Service\MetaBusinessService::class);

            // 1. Quét toàn bộ campaign trong Database chưa Pause
            $metaCampaigns = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUserId)
                ->where('status', '!=', 'PAUSED')
                ->where('status', '!=', 'DELETED')
                ->get();

            $stats['total'] += $metaCampaigns->count();
            $pausedCampaignIds = [];

            foreach ($metaCampaigns as $campaign) {
                $cId = (string) ($campaign->campaign_id ?: $campaign->id);
                
                // Xác định chính xác PlatformSetting của BM Gốc sở hữu tài khoản này
                $settingId = $this->resolveMetaSettingIdForAccount($serviceUser, $campaign->metaAccount, $campaign->metaAccount?->account_id);
                if ($settingId) {
                    $metaBusinessService->setSettingId($settingId);
                }

                $result = $metaBusinessService->updateCampaignStatus($cId, 'PAUSED');
                if ($result->isError()) {
                    $stats['failed']++;
                    $errorMsg = $result->getMessage();
                    if (!in_array($errorMsg, $stats['errors'], true)) {
                        $stats['errors'][] = $errorMsg;
                    }
                    Logging::web('ServicesBillPostpay: Failed to pause Meta campaign', [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $cId,
                        'setting_id' => $settingId,
                        'error' => $errorMsg,
                    ]);
                } else {
                    $stats['success']++;
                    $pausedCampaignIds[] = $cId;
                }

                // Cập nhật trạng thái trong database
                $campaign->status = 'PAUSED';
                $campaign->effective_status = 'PAUSED';
                $campaign->save();
            }

            // 2. Quét trực tiếp Meta API trên tất cả tài khoản để bắt sạch 100% campaign khách mới tạo trên Ads Manager
            $metaAccounts = DB::table('meta_accounts')
                ->where('service_user_id', $serviceUserId)
                ->whereNull('deleted_at')
                ->get();

            foreach ($metaAccounts as $account) {
                try {
                    $rawAccId = (string) ($account->account_id ?? '');
                    if (!$rawAccId) {
                        continue;
                    }
                    $normalizedAccId = str_starts_with($rawAccId, 'act_') ? $rawAccId : 'act_' . $rawAccId;

                    $settingId = $this->resolveMetaSettingIdForAccount($serviceUser, $account, $rawAccId);
                    if ($settingId) {
                        $metaBusinessService->setSettingId($settingId);
                    }

                    $apiCampaignsResult = $metaBusinessService->getCampaignsPaginated(
                        $normalizedAccId,
                        100
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
                                }

                                // Cập nhật hoặc lưu lại vào DB
                                $this->metaAdsCampaignRepository->query()->updateOrCreate(
                                    [
                                        'campaign_id' => $cId,
                                        'service_user_id' => $serviceUserId,
                                    ],
                                    [
                                        'meta_account_id' => $account->id,
                                        'name' => $campData['name'] ?? ('Campaign ' . $cId),
                                        'status' => 'PAUSED',
                                        'effective_status' => 'PAUSED',
                                        'last_synced_at' => now(),
                                    ]
                                );
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

    /**
     * Tìm chính xác PlatformSetting ID của BM Gốc sở hữu tài khoản Ads
     */
    public function resolveMetaSettingIdForAccount($serviceUser, $metaAccount = null, ?string $accountId = null): ?string
    {
        $platformSettingService = app(\App\Service\PlatformSettingService::class);
        $candidateBmIds = [];

        // 1. Lấy từ metaAccount nếu có
        if ($metaAccount && !empty($metaAccount->business_manager_id)) {
            $candidateBmIds[] = (string) $metaAccount->business_manager_id;
        }

        // 2. Lấy từ config của service_user
        if ($serviceUser) {
            $config = $serviceUser->config_account ?? [];
            if (!empty($config['child_bm_id'])) {
                $candidateBmIds[] = (string) $config['child_bm_id'];
            }
            if (!empty($config['business_manager_id'])) {
                $candidateBmIds[] = (string) $config['business_manager_id'];
            }
            if (!empty($config['bm_id'])) {
                $candidateBmIds[] = (string) $config['bm_id'];
            }
        }

        // 3. Tra từ bảng meta_account_business_manager_accesses theo account_id
        $targetAccId = $accountId ?: $metaAccount?->account_id;
        if ($targetAccId) {
            $rawId = preg_replace('/^act_/', '', (string) $targetAccId);
            $normalizedId = 'act_' . $rawId;
            $accesses = \App\Models\MetaAccountBusinessManagerAccess::where('account_id', $rawId)
                ->orWhere('account_id', $normalizedId)
                ->get();
            foreach ($accesses as $acc) {
                if (!empty($acc->source_bm_id)) {
                    $candidateBmIds[] = (string) $acc->source_bm_id;
                }
                if (!empty($acc->owner_bm_id)) {
                    $candidateBmIds[] = (string) $acc->owner_bm_id;
                }
            }
        }

        // Duyệt qua các BM ID và quy đổi BM con -> BM Gốc (parent_bm_id)
        $checkedBmIds = [];
        foreach ($candidateBmIds as $bmId) {
            if (!$bmId || in_array($bmId, $checkedBmIds, true)) {
                continue;
            }
            $checkedBmIds[] = $bmId;

            // Kiểm tra xem có BM cha không (BM con -> BM gốc)
            $bmRecord = \App\Models\MetaBusinessManager::where('bm_id', $bmId)->first();
            $primaryBmId = $bmRecord?->parent_bm_id ?: $bmId;

            // Tìm PlatformSetting theo BM Gốc
            $settingResult = $platformSettingService->findByConfigField(
                \App\Common\Constants\Platform\PlatformType::META->value,
                'business_manager_id',
                (string) $primaryBmId
            );

            if (!$settingResult->isError() && $settingResult->getData()) {
                return (string) $settingResult->getData()->id;
            }

            // Thử tiếp với chính bmId nếu parent_bm_id khác bmId
            if ($primaryBmId !== $bmId) {
                $settingDirect = $platformSettingService->findByConfigField(
                    \App\Common\Constants\Platform\PlatformType::META->value,
                    'business_manager_id',
                    (string) $bmId
                );
                if (!$settingDirect->isError() && $settingDirect->getData()) {
                    return (string) $settingDirect->getData()->id;
                }
            }
        }

        return null;
    }
}
