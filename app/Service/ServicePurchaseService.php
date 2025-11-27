<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\ServiceUserTransactionLog;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;

class ServicePurchaseService
{
    public function __construct(
        protected ServicePackageRepository $servicePackageRepository,
        protected ServiceUserRepository $serviceUserRepository,
        protected WalletRepository $walletRepository,
        protected UserWalletTransactionRepository $walletTransactionRepository,
    ) {
    }

    // Tạo order mua dịch vụ và trừ tiền ví nội bộ
     
    public function createPurchaseOrder(
        int $userId,
        string $packageId,
        float $topUpAmount = 0,
        float $budget = 0,
        array $configAccount = []
    ): ServiceReturn {
        try {
            return DB::transaction(function () use ($userId, $packageId, $topUpAmount, $budget, $configAccount) {
                $package = $this->servicePackageRepository->find($packageId);
                if (!$package) {
                    return ServiceReturn::error(message: __('Gói dịch vụ không tồn tại'));
                }

                if ($package->disabled) {
                    return ServiceReturn::error(message: __('Gói dịch vụ đã bị vô hiệu hóa'));
                }

                $topUpAmount = max(0, $topUpAmount);
                $minTopUp = (float) $package->range_min_top_up;
                if ($topUpAmount > 0 && $minTopUp > 0 && $topUpAmount < $minTopUp) {
                    return ServiceReturn::error(
                        message: __('Số tiền top-up tối thiểu là :amount USD', ['amount' => number_format($minTopUp, 2)])
                    );
                }

                $openFee = (float) $package->open_fee;
                $serviceFeePercent = (float) $package->top_up_fee;
                $serviceFee = $topUpAmount > 0 ? ($topUpAmount * $serviceFeePercent / 100) : 0;
                // Tổng tiền phải trừ ví = phí mở + số tiền top-up + phí dịch vụ top-up
                $totalCost = $openFee + $topUpAmount + $serviceFee;

                $wallet = $this->walletRepository->findByUserId($userId);
                if (!$wallet) {
                    return ServiceReturn::error(message: __('Ví không tồn tại'));
                }

                if ((float) $wallet->balance < $totalCost) {
                    return ServiceReturn::error(message: __('Số dư ví không đủ'));
                }

                $wallet->update(['balance' => (float) $wallet->balance - $totalCost]);

                $defaultConfig = $this->getDefaultConfigAccount($package->platform, $configAccount);
                $serviceUser = $this->serviceUserRepository->create([
                    'package_id' => $packageId,
                    'user_id' => $userId,
                    'config_account' => $defaultConfig,
                    'status' => ServiceUserStatus::PENDING->value,
                    'budget' => max(0, $budget), //tránh âm
                    'description' => "Mua gói dịch vụ: {$package->name}",
                ]);

                // Lưu lịch sử ví: số âm để thể hiện trừ tiền
                $walletTransaction = $this->walletTransactionRepository->create([
                    'wallet_id' => $wallet->id,
                    'amount' => -$totalCost,
                    'type' => WalletTransactionType::SERVICE_PURCHASE->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'description' => "Thanh toán dịch vụ: {$package->name}",
                    'reference_id' => (string) $serviceUser->id,
                ]);

                ServiceUserTransactionLog::create([
                    'service_user_id' => $serviceUser->id,
                    'amount' => $totalCost,
                    'type' => ServiceUserTransactionType::PURCHASE->value,
                    'status' => ServiceUserTransactionStatus::COMPLETED->value,
                    'reference_id' => (string) $walletTransaction->id,
                    'description' => "Thanh toán mua gói dịch vụ: {$package->name}",
                ]);

                Logging::web('ServicePurchaseService@createPurchaseOrder: Wallet deducted', [
                    'wallet_id' => $wallet->id,
                    'service_user_id' => $serviceUser->id,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'amount' => $totalCost,
                ]);

                return ServiceReturn::success(data: [
                    'service_user_id' => $serviceUser->id,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'total_cost' => $totalCost,
                ]);
            });
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServicePurchaseService@createPurchaseOrder error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy default config account theo platform
     * @param int $platform
     * @param array $userConfig
     * @return array
     */
    private function getDefaultConfigAccount(int $platform, array $userConfig = []): array
    {
        if ($platform === PlatformType::META->value) {
            return [
                'meta_email' => $userConfig['meta_email'] ?? '',
                'display_name' => $userConfig['display_name'] ?? '',
                'bm_id' => $userConfig['bm_id'] ?? '',
            ];
        }

        // Google Ads config (nếu cần)
        return $userConfig;
    }
}

