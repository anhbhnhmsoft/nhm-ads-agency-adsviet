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

class DashboardService
{
    public function __construct(
        protected GoogleAccountRepository $googleAccountRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected TicketRepository $ticketRepository,
    ) {
    }

    /**
     * Lấy thống kê tài khoản Google và Meta
     * @return ServiceReturn
     */
    public function getPlatformAccountsStats(): ServiceReturn
    {
        try {
            // Thống kê Google Ads accounts
            $googleAccounts = $this->googleAccountRepository->query()->get();
            $googleActiveAccounts = $googleAccounts
                ->where('account_status', GoogleCustomerStatus::ENABLED->value)
                ->count();
            
            // Tính tổng balance
            $googleTotalBalance = 0.0;
            foreach ($googleAccounts as $account) {
                if ($account->balance !== null) {
                    $googleTotalBalance += (float) $account->balance;
                }
            }

            // Thống kê Meta Ads accounts
            $metaAccounts = $this->metaAccountRepository->query()->get();
            $metaActiveAccounts = $metaAccounts
                ->where('account_status', MetaAdsAccountStatus::ACTIVE->value)
                ->count();
            
            // Tính tổng balance
            $metaTotalBalance = 0.0;
            foreach ($metaAccounts as $account) {
                if ($account->balance !== null) {
                    $metaTotalBalance += (float) $account->balance;
                }
            }

            return ServiceReturn::success(data: [
                'google' => [
                    'active_accounts' => $googleActiveAccounts,
                    'total_balance' => number_format($googleTotalBalance, 2, '.', ''),
                ],
                'meta' => [
                    'active_accounts' => $metaActiveAccounts,
                    'total_balance' => number_format($metaTotalBalance, 2, '.', ''),
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

