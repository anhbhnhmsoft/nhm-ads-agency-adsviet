<?php

namespace App\Service;

use App\Common\Constants\Google\GoogleCustomerStatus;
use App\Common\Constants\ServicePackage\Meta\MetaAdsAccountStatus;
use App\Common\Constants\Ticket\TicketStatus;
use App\Common\Constants\Ticket\TicketMetadataType;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\TicketRepository;
use App\Service\PlatformSettingService;

class DashboardService
{
    public function __construct(
        protected GoogleAccountRepository $googleAccountRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected TicketRepository $ticketRepository,
        protected PlatformSettingService $platformSettingService,
    ) {
    }

    /**
     * Lấy thống kê tài khoản Google và Meta
     * @return ServiceReturn
     */
    public function getPlatformAccountsStats(): ServiceReturn
    {
        try {
            $metaSettingId = session('active_meta_setting_id');
            $googleSettingId = session('active_google_setting_id');

            $googleQuery = $this->googleAccountRepository->query();
            
            // Nếu có chọn MCC cụ thể, lọc theo nó
            if ($googleSettingId) {
                $setting = $this->platformSettingService->find($googleSettingId)->getData();
                if ($setting && isset($setting->config['login_customer_id'])) {
                    $googleQuery->where('customer_manager_id', (string) $setting->config['login_customer_id']);
                }
            }
            
            $googleAccounts = $googleQuery->get();
            $googleActiveAccounts = $googleAccounts
                ->where('account_status', GoogleCustomerStatus::ENABLED->value)
                ->count();
            
            $googleTotalBalance = 0.0;
            $googleBalancesByCurrency = [];
            foreach ($googleAccounts as $account) {
                if ($account->balance !== null) {
                    $balance = (float) $account->balance;
                    $currency = strtoupper((string) ($account->currency ?? 'USD'));

                    $googleTotalBalance += $balance;
                    $googleBalancesByCurrency[$currency] = ($googleBalancesByCurrency[$currency] ?? 0.0) + $balance;
                }
            }

            $metaQuery = $this->metaAccountRepository->query();

            if ($metaSettingId) {
                $setting = $this->platformSettingService->find($metaSettingId)->getData();
                $bmId = $setting
                    ? $this->platformSettingService->getMetaScopedBusinessManagerId($setting->config ?? [])
                    : null;
                if ($bmId) {
                    $metaQuery->where('business_manager_id', $bmId);
                }
            }

            $metaAccounts = $metaQuery->get();
            $metaActiveAccounts = $metaAccounts
                ->where('account_status', MetaAdsAccountStatus::ACTIVE->value)
                ->count();
            
            $metaTotalBalance = 0.0;
            $metaBalancesByCurrency = [];
            foreach ($metaAccounts as $account) {
                if ($account->balance !== null) {
                    $balance = (float) $account->balance;
                    $currency = strtoupper((string) ($account->currency ?? 'USD'));

                    $metaTotalBalance += $balance;
                    $metaBalancesByCurrency[$currency] = ($metaBalancesByCurrency[$currency] ?? 0.0) + $balance;
                }
            }

            return ServiceReturn::success(data: [
                'google' => [
                    'active_accounts' => $googleActiveAccounts,
                    'total_balance' => number_format($googleTotalBalance, 2, '.', ''),
                    'balances_by_currency' => $this->formatBalancesByCurrency($googleBalancesByCurrency),
                    'is_filtered' => !empty($googleSettingId),
                ],
                'meta' => [
                    'active_accounts' => $metaActiveAccounts,
                    'total_balance' => number_format($metaTotalBalance, 2, '.', ''),
                    'balances_by_currency' => $this->formatBalancesByCurrency($metaBalancesByCurrency),
                    'is_filtered' => !empty($metaSettingId),
                ],
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'Lỗi khi lấy thống kê tài khoản platform DashboardService@getPlatformAccountsStats: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function formatBalancesByCurrency(array $balancesByCurrency): array
    {
        ksort($balancesByCurrency);

        return array_map(
            fn (string $currency, float $amount): array => [
                'currency' => $currency,
                'amount' => number_format($amount, 2, '.', ''),
            ],
            array_keys($balancesByCurrency),
            $balancesByCurrency
        );
    }

    /**
     * Lấy thống kê tickets đang yêu cầu xử lý theo type
     * Chỉ đếm các ticket có status: PENDING, OPEN, IN_PROGRESS
     * @return ServiceReturn
     */
    public function getPendingTicketsByType(): ServiceReturn
    {
        try {
            // Các status cần đếm (chưa resolved/closed)
            $activeStatuses = [
                TicketStatus::PENDING->value,
                TicketStatus::OPEN->value,
                TicketStatus::IN_PROGRESS->value,
            ];

            $transferBudget = $this->ticketRepository->query()
                ->whereIn('status', $activeStatuses)
                ->whereJsonContains('metadata->type', TicketMetadataType::TRANSFER->value)
                ->count();

            $accountLiquidation = $this->ticketRepository->query()
                ->whereIn('status', $activeStatuses)
                ->whereJsonContains('metadata->type', TicketMetadataType::REFUND->value)
                ->count();

            $accountAppeal = $this->ticketRepository->query()
                ->whereIn('status', $activeStatuses)
                ->whereJsonContains('metadata->type', TicketMetadataType::APPEAL->value)
                ->count();

            $shareBm = $this->ticketRepository->query()
                ->whereIn('status', $activeStatuses)
                ->whereJsonContains('metadata->type', TicketMetadataType::SHARE->value)
                ->count();

            $walletWithdraw = $this->ticketRepository->query()
                ->whereIn('status', $activeStatuses)
                ->whereJsonContains('metadata->type', TicketMetadataType::WALLET_WITHDRAW_APP->value)
                ->count();

            return ServiceReturn::success(data: [
                'transfer_budget' => $transferBudget,
                'account_liquidation' => $accountLiquidation,
                'account_appeal' => $accountAppeal,
                'share_bm' => $shareBm,
                'wallet_withdraw_app' => $walletWithdraw,
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'Lỗi khi lấy thống kê tickets DashboardService@getPendingTicketsByType: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
