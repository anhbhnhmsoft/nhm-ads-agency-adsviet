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
        $startDate = $service->created_at;
        $daysActive = $now->diffInDays($startDate);

        // Phải đạt tối thiểu 30 ngày sử dụng
        if ($daysActive < 30) {
            return ServiceReturn::error("Service hasn't reached 30 days yet.");
        }

        // Tìm giao dịch cashback gần nhất cho dịch vụ này
        $lastCashback = $this->transactionRepository->getLastCashbackForService(
            $service->id,
            WalletTransactionType::CASHBACK->value
        );

        $calculationStartDate = null;
        $calculationEndDate = $now->copy()->startOfDay();

        if (!$lastCashback) {
             $calculationStartDate = $startDate->copy()->startOfDay();
        } else {
            $lastProcessedDate = $lastCashback->created_at;
            if ($now->diffInDays($lastProcessedDate) < 30) {
                return ServiceReturn::error("Last cashback was less than 30 days ago.");
            }
            $calculationStartDate = $lastProcessedDate->copy()->startOfDay();
        }

        // Tính tổng chi tiêu trong kỳ
        $totalSpend = $this->calculateTotalSpend($service, $calculationStartDate, $calculationEndDate);

        if ($totalSpend <= 0) {
            return ServiceReturn::error("No spend found for calculation period.");
        }

        $cashbackPercent = (float) $service->package->cashback_percent;
        $cashbackAmount = ($totalSpend * $cashbackPercent) / 100;

        if ($cashbackAmount <= 0) {
            return ServiceReturn::error("Calculated cashback amount is 0.");
        }

        return $this->executePayout($service, $cashbackAmount, $cashbackPercent);
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
     * Thực hiện cộng tiền vào ví và tạo giao dịch.
     */
    protected function executePayout(ServiceUser $service, float $amount, float $percent): ServiceReturn
    {
        return DB::transaction(function () use ($service, $amount, $percent) {
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
                'description' => __('wallet.transaction_description.cashback', ['percent' => $percent]),
                'customer_name' => $service->user->name,
                'customer_email' => $service->user->username,
            ]);

            return ServiceReturn::success(null, "Cashback payout successful.");
        });
    }
}
