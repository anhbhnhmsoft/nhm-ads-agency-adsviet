<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
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
    protected $description = 'Tính phí postpay (30 ngày gần nhất) cho các dịch vụ payment_type = postpay';

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
            ->where('config_account->payment_type', 'postpay')
            ->chunkById(100, function ($serviceUsers) use ($today) {
                foreach ($serviceUsers as $serviceUser) {
                    try {
                        $config = $serviceUser->config_account ?? [];
                        $postPaymentDate = $config['post_payment_date'] ?? null;
                        $postpayDays = isset($config['postpay_days']) && is_numeric($config['postpay_days']) 
                            ? (int) $config['postpay_days'] 
                            : 30; // Mặc định 30 ngày

                        if (!$postPaymentDate) {
                            $baseDate = $serviceUser->last_postpay_billed_at 
                                ? $serviceUser->last_postpay_billed_at 
                                : $serviceUser->created_at;
                            $postPaymentDate = $baseDate->copy()->addDays($postpayDays)->format('Y-m-d');
                            $config['post_payment_date'] = $postPaymentDate;
                            $config['postpay_days'] = $postpayDays;
                            $serviceUser->config_account = $config;
                            $serviceUser->save();
                        }

                        $postPaymentDateCarbon = Carbon::parse($postPaymentDate);
                        
                        // Chỉ bill khi đã đến hoặc quá ngày thanh toán
                        if ($today->lt($postPaymentDateCarbon)) {
                            continue;
                        }

                        $windowStartDate = $serviceUser->last_postpay_billed_at
                            ? $serviceUser->last_postpay_billed_at->toDateString()
                            : $serviceUser->created_at->toDateString();

                        $spending = $this->getSpendingBetween($serviceUser->id, $windowStartDate, $today->toDateString());
                        $package = $serviceUser->package;
                        if (!$package || empty($package->monthly_spending_fee_structure)) {
                            $serviceUser->last_postpay_billed_at = $today;
                            $serviceUser->save();
                            continue;
                        }

                        $feePercent = $this->getMonthlyFeePercent($spending, $package->monthly_spending_fee_structure);
                        if ($feePercent === null || $feePercent <= 0 || $spending <= 0) {
                            $serviceUser->last_postpay_billed_at = $today;
                            $serviceUser->save();
                            continue;
                        }

                        $monthlyFee = $spending * ($feePercent / 100);

                        // Thu phí mở tài khoản ở kỳ postpay đầu tiên
                        $paymentType = $config['payment_type'] ?? 'prepay';
                        $openFeePaid = $config['open_fee_paid'] ?? ($paymentType === 'prepay');
                        $openFee = (!$openFeePaid && $paymentType === 'postpay') ? (float) $package->open_fee : 0;

                        $chargeAmount = $monthlyFee + $openFee;
                        if ($chargeAmount <= 0) {
                            $serviceUser->last_postpay_billed_at = $today;
                            $serviceUser->save();
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

                        if ((float) $wallet->balance < $chargeAmount) {
                            // Số dư không đủ: cảnh báo, pause campaign, và bỏ qua (không cập nhật last_postpay_billed_at để lần sau thử lại)
                            Logging::web('services:bill-postpay insufficient balance, pause campaigns', [
                                'service_user_id' => $serviceUser->id,
                                'user_id' => $serviceUser->user_id,
                                'balance' => $wallet->balance,
                                'monthly_fee' => $monthlyFee,
                                'open_fee' => $openFee,
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
                                $openFeeFormatted = number_format($openFee, 2);
                                $monthlyFeeFormatted = number_format($monthlyFee, 2);
                                $message = __('wallet.postpay_charge_insufficient', [
                                    'name' => $shortName,
                                    'balance' => $balanceFormatted,
                                    'charge' => $chargeFormatted,
                                    'monthly_fee' => $monthlyFeeFormatted,
                                    'open_fee' => $openFeeFormatted,
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

                        DB::transaction(function () use ($wallet, $openFee, $chargeAmount, $package, $serviceUser, $today, $config, $openFeePaid) {
                            $wallet->update(['balance' => (float) $wallet->balance - $chargeAmount]);

                            $walletTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => -$chargeAmount,
                                'type' => WalletTransactionType::SERVICE_PURCHASE->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => "Postpay monthly fee: {$package->name}",
                                'reference_id' => (string) $serviceUser->id,
                            ]);

                            ServiceUserTransactionLog::create([
                                'service_user_id' => $serviceUser->id,
                                'amount' => $chargeAmount,
                                'type' => ServiceUserTransactionType::FEE->value,
                                'status' => ServiceUserTransactionStatus::COMPLETED->value,
                                'reference_id' => (string) $walletTransaction->id,
                                'description' => "Postpay monthly fee (last 30d): {$package->name}",
                            ]);

                            // Đánh dấu đã thu phí mở tài khoản (chỉ một lần cho trả sau)
                            if (!$openFeePaid && $openFee > 0) {
                                $config['open_fee_paid'] = true;
                            }

                            // Cập nhật post_payment_date cho kỳ tiếp theo (dùng postpay_days từ config)
                            $postpayDays = isset($config['postpay_days']) && is_numeric($config['postpay_days']) 
                                ? (int) $config['postpay_days'] 
                                : 30; // Mặc định 30 ngày nếu không có
                            $config['post_payment_date'] = $today->copy()->addDays($postpayDays)->format('Y-m-d');
                            $serviceUser->config_account = $config;
                            $serviceUser->last_postpay_billed_at = $today;
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
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        // Google spend
        $googleSpend = (float) DB::table('google_ads_account_insights')
            ->where('service_user_id', $serviceUserId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));

        return $metaSpend + $googleSpend;
    }

    /**
     * Tìm fee percent theo tier min-max (range lưu dạng "min-max" hoặc "min+")
     */
    private function getMonthlyFeePercent(float $spending, array $tiers): ?float
    {
        foreach ($tiers as $tier) {
            $range = $tier['range'] ?? '';
            $feePercentRaw = $tier['fee_percent'] ?? '';
            $feePercent = $this->parseNumber($feePercentRaw);
            if ($feePercent === null || $feePercent <= 0) {
                continue;
            }

            [$min, $max] = $this->parseRange($range);
            if ($min === null) {
                continue;
            }
            $match = $spending >= $min && ($max === null || $spending <= $max);
            if ($match) {
                return $feePercent;
            }
        }

        return null;
    }

    private function parseRange(string $range): array
    {
        $cleaned = str_replace(['$', ','], '', trim($range));
        if ($cleaned === '') {
            return [null, null];
        }
        $parts = preg_split('/[-–]/', $cleaned);
        if (count($parts) >= 2) {
            $min = $this->parseNumber($parts[0]);
            $max = $this->parseNumber($parts[1]);
            return [$min, $max];
        }
        if (str_ends_with($cleaned, '+')) {
            $min = $this->parseNumber(substr($cleaned, 0, -1));
            return [$min, null];
        }
        $min = $this->parseNumber($cleaned);
        return [$min, null];
    }

    private function parseNumber(string $value): ?float
    {
        $clean = trim(str_replace(['%', ',', '$'], '', $value));
        if ($clean === '') {
            return null;
        }
        $parsed = (float) $clean;
        return is_finite($parsed) ? $parsed : null;
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

