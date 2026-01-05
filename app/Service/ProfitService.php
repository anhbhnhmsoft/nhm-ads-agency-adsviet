<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserWalletTransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserWalletTransactionRepository $walletTransactionRepository,
    ) {
    }

    /**
     * Lấy danh sách customer mà agency quản lý
     */
    protected function getManagedCustomerIds(int $agencyId): array
    {
        return $this->userReferralRepository->query()
            ->where('referrer_id', $agencyId)
            ->whereNull('deleted_at')
            ->pluck('referred_id')
            ->toArray();
    }

    /**
     * Tính lợi nhuận theo từng khách hàng cho agency
     * 
     * @param int|null $customerId - Nếu null thì lấy tất cả customer
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return ServiceReturn
     */
    public function getProfitByCustomer(?int $customerId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ agency mới được xem
            if ($user->role !== UserRole::AGENCY->value) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Lấy danh sách customer mà agency quản lý
            $managedCustomerIds = $this->getManagedCustomerIds((int) $user->id);
            
            if (empty($managedCustomerIds)) {
                return ServiceReturn::success(data: []);
            }

            // Nếu có filter theo customer cụ thể
            if ($customerId) {
                if (!in_array($customerId, $managedCustomerIds)) {
                    return ServiceReturn::error(message: __('common_error.permission_denied'));
                }
                $customerIds = [$customerId];
            } else {
                $customerIds = $managedCustomerIds;
            }

            // Lấy thông tin customer
            $customers = DB::table('users')
                ->whereIn('id', $customerIds)
                ->select('id', 'name', 'username', 'email')
                ->get()
                ->keyBy('id');

            $result = [];

            foreach ($customerIds as $customerId) {
                $customer = $customers->get($customerId);
                if (!$customer) {
                    continue;
                }

                // Tính doanh thu (revenue) từ các giao dịch nạp tiền của customer
                $revenueQuery = $this->walletTransactionRepository->query()
                    ->where('user_id', $customerId)
                    ->where('type', 'deposit') // Loại giao dịch nạp tiền
                    ->where('status', 'completed');

                if ($startDate && $endDate) {
                    $revenueQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                $revenue = (float) $revenueQuery->sum('amount') ?? 0;

                // Tính chi phí (cost) từ các giao dịch mua dịch vụ
                $costQuery = $this->walletTransactionRepository->query()
                    ->where('user_id', $customerId)
                    ->where('type', 'service_purchase') // Loại giao dịch mua dịch vụ
                    ->where('status', 'completed');

                if ($startDate && $endDate) {
                    $costQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                $cost = (float) $costQuery->sum('amount') ?? 0;

                // Tính lợi nhuận = doanh thu - chi phí
                $profit = $revenue - $cost;
                $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                // Lấy thống kê theo platform
                $platformStats = $this->getPlatformStats($customerId, $startDate, $endDate);

                $result[] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customer->name ?? $customer->username ?? 'Unknown',
                    'customer_email' => $customer->email ?? '',
                    'revenue' => number_format($revenue, 2, '.', ''),
                    'cost' => number_format($cost, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'profit_margin' => number_format($profitMargin, 2, '.', ''),
                    'platform_stats' => $platformStats,
                ];
            }

            // Sắp xếp theo lợi nhuận giảm dần
            usort($result, function ($a, $b) {
                return (float) $b['profit'] <=> (float) $a['profit'];
            });

            return ServiceReturn::success(data: $result);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ProfitService@getProfitByCustomer error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tính lợi nhuận theo nền tảng (Facebook/Google)
     * 
     * @param int|null $platform - PlatformType::META hoặc PlatformType::GOOGLE, null = tất cả
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return ServiceReturn
     */
    public function getProfitByPlatform(?int $platform = null, ?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Agency hoặc Admin mới được xem
            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::ADMIN->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $customerIds = [];
            if ($user->role === UserRole::AGENCY->value) {
                // Agency: chỉ xem customer của mình
                $customerIds = $this->getManagedCustomerIds((int) $user->id);
                if (empty($customerIds)) {
                    return ServiceReturn::success(data: []);
                }
            }
            // Admin: xem tất cả

            $platforms = $platform ? [$platform] : [PlatformType::META->value, PlatformType::GOOGLE->value];
            $result = [];

            foreach ($platforms as $platformType) {
                // Lấy service_users theo platform
                $query = $this->serviceUserRepository->query()
                    ->whereHas('package', function ($q) use ($platformType) {
                        $q->where('platform', $platformType);
                    });

                if (!empty($customerIds)) {
                    $query->whereIn('user_id', $customerIds);
                }

                $serviceUsers = $query->get();

                $revenue = 0;
                $cost = 0;

                foreach ($serviceUsers as $serviceUser) {
                    $userId = $serviceUser->user_id;

                    // Tính doanh thu từ nạp tiền
                    $revenueQuery = $this->walletTransactionRepository->query()
                        ->where('user_id', $userId)
                        ->where('type', 'deposit')
                        ->where('status', 'completed');

                    if ($startDate && $endDate) {
                        $revenueQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }

                    $revenue += (float) $revenueQuery->sum('amount') ?? 0;

                    // Tính chi phí từ mua dịch vụ
                    $costQuery = $this->walletTransactionRepository->query()
                        ->where('user_id', $userId)
                        ->where('type', 'service_purchase')
                        ->where('status', 'completed');

                    if ($startDate && $endDate) {
                        $costQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }

                    $cost += (float) $costQuery->sum('amount') ?? 0;
                }

                $profit = $revenue - $cost;
                $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $platformName = $platformType === PlatformType::META->value ? 'Facebook Ads' : 'Google Ads';

                $result[] = [
                    'platform' => $platformType,
                    'platform_name' => $platformName,
                    'revenue' => number_format($revenue, 2, '.', ''),
                    'cost' => number_format($cost, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'profit_margin' => number_format($profitMargin, 2, '.', ''),
                ];
            }

            return ServiceReturn::success(data: $result);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ProfitService@getProfitByPlatform error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy thống kê theo platform cho một customer
     */
    protected function getPlatformStats(int $customerId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $stats = [];

        // Meta/Facebook
        $metaServiceUsers = $this->serviceUserRepository->query()
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::META->value);
            })
            ->get();

        $metaRevenue = 0;
        $metaCost = 0;
        foreach ($metaServiceUsers as $serviceUser) {
            $revenueQuery = $this->walletTransactionRepository->query()
                ->where('user_id', $customerId)
                ->where('type', 'deposit')
                ->where('status', 'completed');

            if ($startDate && $endDate) {
                $revenueQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $metaRevenue += (float) $revenueQuery->sum('amount') ?? 0;

            $costQuery = $this->walletTransactionRepository->query()
                ->where('user_id', $customerId)
                ->where('type', 'service_purchase')
                ->where('status', 'completed');

            if ($startDate && $endDate) {
                $costQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $metaCost += (float) $costQuery->sum('amount') ?? 0;
        }

        $stats['meta'] = [
            'revenue' => number_format($metaRevenue, 2, '.', ''),
            'cost' => number_format($metaCost, 2, '.', ''),
            'profit' => number_format($metaRevenue - $metaCost, 2, '.', ''),
        ];

        // Google
        $googleServiceUsers = $this->serviceUserRepository->query()
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::GOOGLE->value);
            })
            ->get();

        $googleRevenue = 0;
        $googleCost = 0;
        foreach ($googleServiceUsers as $serviceUser) {
            $revenueQuery = $this->walletTransactionRepository->query()
                ->where('user_id', $customerId)
                ->where('type', 'deposit')
                ->where('status', 'completed');

            if ($startDate && $endDate) {
                $revenueQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $googleRevenue += (float) $revenueQuery->sum('amount') ?? 0;

            $costQuery = $this->walletTransactionRepository->query()
                ->where('user_id', $customerId)
                ->where('type', 'service_purchase')
                ->where('status', 'completed');

            if ($startDate && $endDate) {
                $costQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $googleCost += (float) $costQuery->sum('amount') ?? 0;
        }

        $stats['google'] = [
            'revenue' => number_format($googleRevenue, 2, '.', ''),
            'cost' => number_format($googleCost, 2, '.', ''),
            'profit' => number_format($googleRevenue - $googleCost, 2, '.', ''),
        ];

        return $stats;
    }
}

