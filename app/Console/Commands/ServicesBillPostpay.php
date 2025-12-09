<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Models\ServiceUserTransactionLog;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
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
                        $windowStartDate = $serviceUser->last_postpay_billed_at
                            ? $serviceUser->last_postpay_billed_at->toDateString()
                            : $serviceUser->created_at->toDateString();

                        // Chỉ bill khi đã đủ 30 ngày kể từ mốc windowStartDate
                        $daysElapsed = Carbon::parse($windowStartDate)->diffInDays($today);
                        if ($daysElapsed < 30) {
                            continue;
                        }

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

                        if ((float) $wallet->balance < $monthlyFee) {
                            // Phương án A: cảnh báo và bỏ qua (không cập nhật last_postpay_billed_at để lần sau thử lại)
                            Logging::web('services:bill-postpay insufficient balance, skip', [
                                'service_user_id' => $serviceUser->id,
                                'user_id' => $serviceUser->user_id,
                                'balance' => $wallet->balance,
                                'monthly_fee' => $monthlyFee,
                            ]);
                            $serviceUser->last_postpay_billed_at = $today;
                            $serviceUser->save();
                            continue;
                        }

                        DB::transaction(function () use ($wallet, $monthlyFee, $package, $serviceUser, $today) {
                            $wallet->update(['balance' => (float) $wallet->balance - $monthlyFee]);

                            $walletTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => -$monthlyFee,
                                'type' => WalletTransactionType::SERVICE_PURCHASE->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => "Postpay monthly fee: {$package->name}",
                                'reference_id' => (string) $serviceUser->id,
                            ]);

                            ServiceUserTransactionLog::create([
                                'service_user_id' => $serviceUser->id,
                                'amount' => $monthlyFee,
                                'type' => ServiceUserTransactionType::FEE->value,
                                'status' => ServiceUserTransactionStatus::COMPLETED->value,
                                'reference_id' => (string) $walletTransaction->id,
                                'description' => "Postpay monthly fee (last 30d): {$package->name}",
                            ]);

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
}

