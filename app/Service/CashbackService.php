<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Repositories\GoogleAdsAccountInsightRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use App\Service\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashbackService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaAdsAccountInsightRepository $metaInsightRepository,
        protected GoogleAdsAccountInsightRepository $googleInsightRepository,
        protected UserWalletTransactionRepository $transactionRepository,
        protected WalletRepository $walletRepository,
        protected WalletService $walletService
    ) {
    }

    /**
     * Duyệt qua tất cả các dịch vụ đang hoạt động có cấu hình cashback và xử lý trả thưởng.
     */
    public function processAllActiveServices(): void
    {
        $activeServices = $this->serviceUserRepository->getActiveServicesWithCashback();

        foreach ($activeServices as $service) {
            try {
                $this->payoutCashbackForService($service);
            } catch (\Throwable $e) {
                Logging::error("CashbackService error for service {$service->id}", [
                    'exception' => $e
                ]);
            }
        }
    }

    /**
     * Xử lý trả thưởng cashback cho một dịch vụ cụ thể.
     */
    public function payoutCashbackForService(ServiceUser $service): ServiceReturn
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $period = $periodStart->format('Y-m');

        if ($service->created_at->gt($periodEnd)) {
            return ServiceReturn::error('Service was not active in cashback period.');
        }

        $existingCashback = $this->transactionRepository->query()
            ->where('reference_id', (string) $service->id)
            ->where('type', WalletTransactionType::CASHBACK->value)
            ->where('withdraw_info->period', $period)
            ->first();
        if ($existingCashback) {
            return ServiceReturn::error('Cashback already paid for this period.');
        }

        $calculationStartDate = $service->created_at->gt($periodStart)
            ? $service->created_at->copy()->startOfDay()
            : $periodStart;

        $totalSpend = $this->calculateTotalSpend($service, $calculationStartDate, $periodEnd);

        if ($totalSpend <= 0) {
            return ServiceReturn::error("No spend found for calculation period.");
        }

        $cashbackPercent = $this->resolveCashbackPercent($totalSpend, $service->package->monthly_spending_fee_structure ?? []);
        if ($cashbackPercent === null || $cashbackPercent <= 0) {
            return ServiceReturn::error("No cashback tier matched for calculation period.");
        }

        $cashbackAmount = ($totalSpend * $cashbackPercent) / 100;

        if ($cashbackAmount <= 0) {
            return ServiceReturn::error("Calculated cashback amount is 0.");
        }

        return $this->executePayout(
            $service,
            $cashbackAmount,
            $cashbackPercent,
            $period,
            $calculationStartDate,
            $periodEnd,
            $totalSpend
        );
    }

    /**
     * Tính tổng chi tiêu dựa trên nền tảng của dịch vụ (Meta hoặc Google).
     */
    protected function calculateTotalSpend(ServiceUser $service, Carbon $startDate, Carbon $endDate): float
    {
        $platform = (int) $service->package->platform;

        if ($platform === PlatformType::META->value) {
            return $this->metaInsightRepository->getTotalSpendForServiceUser($service->id, $startDate, $endDate);
        } elseif ($platform === PlatformType::GOOGLE->value) {
            return $this->googleInsightRepository->getTotalSpendForServiceUser($service->id, $startDate, $endDate);
        }

        return 0;
    }

    /**
     * Tìm tỷ lệ cashback theo tier chi tiêu tháng.
     * Dữ liệu vẫn dùng key fee_percent để tương thích cấu trúc JSON hiện có.
     */
    protected function resolveCashbackPercent(float $spending, array $tiers): ?float
    {
        foreach ($tiers as $tier) {
            $range = $tier['range'] ?? '';
            $cashbackPercent = $this->parseNumber((string) ($tier['fee_percent'] ?? ''));
            if ($cashbackPercent === null || $cashbackPercent <= 0) {
                continue;
            }

            [$min, $max] = $this->parseRange((string) $range);
            if ($min === null) {
                continue;
            }

            if ($spending >= $min && ($max === null || $spending <= $max)) {
                return $cashbackPercent;
            }
        }

        return null;
    }

    protected function parseRange(string $range): array
    {
        $cleaned = str_replace(['$', ','], '', trim($range));
        if ($cleaned === '') {
            return [null, null];
        }

        $parts = preg_split('/[-–]/', $cleaned);
        if (count($parts) >= 2) {
            return [$this->parseNumber($parts[0]), $this->parseNumber($parts[1])];
        }

        if (str_ends_with($cleaned, '+')) {
            return [$this->parseNumber(substr($cleaned, 0, -1)), null];
        }

        return [$this->parseNumber($cleaned), null];
    }

    protected function parseNumber(string $value): ?float
    {
        $clean = trim(str_replace(['%', ',', '$'], '', $value));
        if ($clean === '') {
            return null;
        }

        $parsed = (float) $clean;
        return is_finite($parsed) ? $parsed : null;
    }

    /**
     * Thực hiện cộng tiền vào ví và tạo giao dịch.
     */
    protected function executePayout(
        ServiceUser $service,
        float $amount,
        float $percent,
        string $period,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $totalSpend
    ): ServiceReturn
    {
        return DB::transaction(function () use ($service, $amount, $percent, $period, $periodStart, $periodEnd, $totalSpend) {
            $wallet = $this->walletRepository->findByUserId($service->user_id);
            if (!$wallet) {
                $walletResult = $this->walletService->createForUser($service->user_id);
                $wallet = $walletResult->getData();
            }

            // Cập nhật số dư ví
            $wallet->balance += $amount;
            $wallet->save();

            // Tạo bản ghi giao dịch
            $this->transactionRepository->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => WalletTransactionType::CASHBACK->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'reference_id' => $service->id,
                'description' => __('wallet.transaction_description.cashback', [
                    'percent' => $percent,
                    'period' => $period,
                ]),
                'withdraw_info' => [
                    'purpose' => 'cashback',
                    'period' => $period,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'total_spend' => $totalSpend,
                    'cashback_percent' => $percent,
                    'cashback_amount' => $amount,
                ],
                'customer_name' => $service->user->name,
                'customer_email' => $service->user->username,
            ]);

            return ServiceReturn::success(null, "Cashback payout successful.");
        });
    }
}
