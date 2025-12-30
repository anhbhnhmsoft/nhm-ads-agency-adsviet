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
use App\Repositories\ConfigRepository;
use App\Common\Constants\Config\ConfigName;
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
        protected ConfigRepository $configRepository,
        protected ServicePackageService $servicePackageService,
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
                    Logging::error('Package not found: ' . $packageId);
                    return ServiceReturn::error(message: __('Gói dịch vụ không tồn tại'));
                }

                if ($package->disabled) {
                    Logging::error('Package is disabled: ' . $packageId);
                    return ServiceReturn::error(message: __('Gói dịch vụ đã bị vô hiệu hóa'));
                }

                $topUpAmount = max(0, $topUpAmount);
                $minTopUp = (float) $package->range_min_top_up;
                if ($topUpAmount > 0 && $minTopUp > 0 && $topUpAmount < $minTopUp) {
                    Logging::error('Top-up amount too low:', [
                        'topUpAmount' => $topUpAmount,
                        'minTopUp' => $minTopUp,
                    ]);
                    return ServiceReturn::error(
                        message: __('Số tiền top-up tối thiểu là :amount USD', ['amount' => number_format($minTopUp, 2)])
                    );
                }

                $openFee = (float) $package->open_fee;
                $serviceFeePercent = (float) $package->top_up_fee;
                $serviceFee = $topUpAmount > 0 ? ($topUpAmount * $serviceFeePercent / 100) : 0;
                $isPrepay = ($configAccount['payment_type'] ?? 'prepay') === 'prepay';

                // Kiểm tra user có được phép trả sau cho gói này không
                if (!$isPrepay && !$this->servicePackageService->isUserAllowedPostpay($packageId, $userId)) {
                    return ServiceReturn::error(
                        message: __('services.validation.postpay_not_allowed')
                    );
                }

                $openFeePayable = $isPrepay ? $openFee : 0; // Trả sau không thu phí mở tài khoản upfront
                // Tổng tiền phải trừ ví = (phí mở nếu trả trước) + số tiền top-up + phí dịch vụ top-up
                $totalCost = $openFeePayable + $topUpAmount + $serviceFee;

                $wallet = $this->walletRepository->findByUserId($userId);
                if (!$wallet) {
                    Logging::error('Wallet not found for user: ' . $userId);
                    return ServiceReturn::error(message: __('Ví không tồn tại'));
                }

                // Kiểm tra ngưỡng tối thiểu để đăng ký trả sau (cấu hình)
                $postpayMinBalanceRaw = $this->configRepository
                    ->findByKey(ConfigName::POSTPAY_MIN_BALANCE->value)?->value;
                $postpayMinBalance = is_numeric($postpayMinBalanceRaw) ? (float) $postpayMinBalanceRaw : 200;
                if (!$isPrepay && (float) $wallet->balance < $postpayMinBalance) {
                    return ServiceReturn::error(
                        message: __('services.validation.postpay_min_wallet', ['amount' => $postpayMinBalance])
                    );
                }

                if ($totalCost > 0) {
                    if ((float) $wallet->balance < $totalCost) {
                        return ServiceReturn::error(message: __('Số dư ví không đủ'));
                    }
                    $wallet->update(['balance' => (float) $wallet->balance - $totalCost]);
                }

                $configAccount['top_up_amount'] = $topUpAmount;
                if (!isset($configAccount['payment_type'])) {
                    $configAccount['payment_type'] = 'prepay';
                }
                // Đánh dấu đã thu phí mở tài khoản nếu trả trước; trả sau sẽ thu ở kỳ bill đầu tiên
                if (!isset($configAccount['open_fee_paid'])) {
                    $configAccount['open_fee_paid'] = $configAccount['payment_type'] === 'prepay';
                }


                $defaultConfig = $this->getDefaultConfigAccount($package->platform, $configAccount);

                $serviceUser = $this->serviceUserRepository->create([
                    'package_id' => $packageId,
                    'user_id' => $userId,
                    'config_account' => $defaultConfig,
                    'status' => ServiceUserStatus::PENDING->value,
                    'budget' => max(0, $budget), //tránh âm
                    'description' => "Mua gói dịch vụ: {$package->name}",
                ]);

                // Lưu lịch sử ví: chỉ tạo giao dịch khi có thu tiền upfront
                $walletTransaction = null;
                if ($totalCost > 0) {
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
                }

                Logging::web('ServicePurchaseService@createPurchaseOrder: Wallet deducted', [
                    'wallet_id' => $wallet->id,
                    'service_user_id' => $serviceUser->id,
                    'wallet_transaction_id' => $walletTransaction?->id,
                    'amount' => $totalCost,
                ]);

                return ServiceReturn::success(data: [
                    'service_user_id' => $serviceUser->id,
                    'wallet_transaction_id' => $walletTransaction?->id,
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
        if (is_array($userConfig['accounts']) && !empty($userConfig['accounts'])) {
            $accounts = [];
            foreach ($userConfig['accounts'] as $account) {
                $accountData = [
                    'meta_email' => $account['meta_email'] ?? '',
                    'display_name' => $account['display_name'] ?? '',
                    'bm_ids' => $account['bm_ids'] ?? [],
                    'timezone_bm' => $account['timezone_bm'] ?? null,
                    'asset_access' => $account['asset_access'] ?? 'full_asset',
                ];

                if ($platform === PlatformType::META->value) {
                    $accountData['fanpages'] = $account['fanpages'] ?? [];
                    $accountData['websites'] = $account['websites'] ?? [];
                } else {
                    $accountData['websites'] = $account['websites'] ?? [];
                }

                $accountData['bm_ids'] = array_filter($accountData['bm_ids'], fn($v) => !empty(trim($v ?? '')));
                if (isset($accountData['fanpages'])) {
                    $accountData['fanpages'] = array_filter($accountData['fanpages'], fn($v) => !empty(trim($v ?? '')));
                }
                if (isset($accountData['websites'])) {
                    $accountData['websites'] = array_filter($accountData['websites'], fn($v) => !empty(trim($v ?? '')));
                }

                $accounts[] = $accountData;
            }

            return [
                'payment_type' => $userConfig['payment_type'] ?? 'prepay',
                'top_up_amount' => $userConfig['top_up_amount'] ?? 0,
                'open_fee_paid' => $userConfig['open_fee_paid'] ?? false,
                'accounts' => $accounts,
            ];
        }

        $config = [
            'meta_email' => $userConfig['meta_email'] ?? '',
            'display_name' => $userConfig['display_name'] ?? '',
            'bm_id' => $userConfig['bm_id'] ?? '',
            'payment_type' => $userConfig['payment_type'] ?? 'prepay',
            'top_up_amount' => $userConfig['top_up_amount'] ?? 0,
            'asset_access' => $userConfig['asset_access'] ?? 'full_asset',
            'timezone_bm' => $userConfig['timezone_bm'] ?? null,
            'open_fee_paid' => $userConfig['open_fee_paid'] ?? false,
        ];

        // Meta Ads: thêm info_fanpage và info_website
        if ($platform === PlatformType::META->value) {
            $config['info_fanpage'] = $userConfig['info_fanpage'] ?? '';
            $config['info_website'] = $userConfig['info_website'] ?? '';
        }

        return $config;
    }
}

