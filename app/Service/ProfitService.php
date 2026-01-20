<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
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
     * - Doanh thu mỗi order = open_fee + top_up_amount + top_up_amount * top_up_fee%
     * - Chi phí mỗi order   = top_up_amount * supplier_fee_percent%
     * - Lợi nhuận           = Doanh thu - Chi phí
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

                // Lấy tất cả service_users của customer này
                $query = $this->serviceUserRepository->query()
                    ->with('package:id,name,platform,open_fee,top_up_fee,supplier_fee_percent')
                    ->where('user_id', $customerId)
                    ->whereHas('package');

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                $serviceUsers = $query->get();

                $revenue = 0.0;
                $cost = 0.0;

                foreach ($serviceUsers as $serviceUser) {
                    $package = $serviceUser->package;
                    if (!$package) {
                        continue;
                    }

                    $config = $serviceUser->config_account ?? [];
                    $topUpAmount = 0.0;
                    if (is_array($config)) {
                        $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
                    }

                    $openFee = (float) $package->open_fee;
                    $topUpFeePercent = (float) $package->top_up_fee;
                    $supplierFeePercent = (float) ($package->supplier_fee_percent ?? 0);

                    // Doanh thu = tổng số tiền khách hàng đã trả
                    // = open_fee + top_up_amount + top_up_amount * top_up_fee%
                    $itemRevenue = $openFee;
                    if ($topUpAmount > 0) {
                        $itemRevenue += $topUpAmount;
                        if ($topUpFeePercent !== 0.0) {
                            $itemRevenue += $topUpAmount * $topUpFeePercent / 100;
                        }
                    }

                    // Chi phí nhà cung cấp (chỉ áp trên top_up_amount)
                    $itemCost = 0.0;
                    if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                        $itemCost += $topUpAmount * $supplierFeePercent / 100;
                    }

                    $revenue += $itemRevenue;
                    $cost += $itemCost;
                }

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

            // - Lợi nhuận được tính dựa trên tổng số tiền khách hàng đã trả và chi phí nhà cung cấp.
            // - Công thức cho 1 lần mua gói (1 service_user):
            //   + Doanh thu (revenue_item) = open_fee + top_up_amount + top_up_amount * top_up_fee%
            //     (Tổng số tiền khách hàng đã trả: phí mở + số tiền nạp + phí dịch vụ)
            //   + Chi phí (cost_item) = top_up_amount * supplier_fee_percent%
            //     (Chi phí nhà cung cấp tính trên số tiền nạp)
            //   + Lợi nhuận (profit_item) = revenue_item - cost_item
            //   Trong đó top_up_amount được lưu trong config_account của service_users.

            $platforms = $platform ? [$platform] : [PlatformType::META->value, PlatformType::GOOGLE->value];
            $result = [];

            foreach ($platforms as $platformType) {
                // Lấy service_users theo platform
                $query = $this->serviceUserRepository->query()
                    ->with('package:id,name,platform,open_fee,top_up_fee,supplier_fee_percent')
                    ->whereHas('package', function ($q) use ($platformType) {
                        $q->where('platform', $platformType);
                    });

                if (!empty($customerIds)) {
                    $query->whereIn('user_id', $customerIds);
                }

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                $serviceUsers = $query->get();

                $revenue = 0.0;
                $cost = 0.0;

                foreach ($serviceUsers as $serviceUser) {
                    $package = $serviceUser->package;
                    if (!$package) {
                        continue;
                    }

                    $config = $serviceUser->config_account ?? [];
                    $topUpAmount = 0.0;
                    if (is_array($config)) {
                        $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
                    }

                    $openFee = (float) $package->open_fee;
                    $topUpFeePercent = (float) $package->top_up_fee;
                    $supplierFeePercent = (float) ($package->supplier_fee_percent ?? 0);

                    // Doanh thu = tổng số tiền khách hàng đã trả
                    // = open_fee + top_up_amount + top_up_amount * top_up_fee%
                    // = open_fee + top_up_amount * (1 + top_up_fee%)
                    $itemRevenue = $openFee;
                    if ($topUpAmount > 0) {
                        $itemRevenue += $topUpAmount;
                        if ($topUpFeePercent !== 0.0) {
                            $itemRevenue += $topUpAmount * $topUpFeePercent / 100;
                        }
                    }

                    // Chi phí nhà cung cấp (chỉ áp trên top_up_amount)
                    $itemCost = 0.0;
                    if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                        $itemCost += $topUpAmount * $supplierFeePercent / 100;
                    }

                    $revenue += $itemRevenue;
                    $cost += $itemCost;
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
        $query = $this->serviceUserRepository->query()
            ->with('package:id,name,platform,open_fee,top_up_fee,supplier_fee_percent')
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::META->value);
            });

            if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $metaServiceUsers = $query->get();

        $metaRevenue = 0.0;
        $metaCost = 0.0;
        foreach ($metaServiceUsers as $serviceUser) {
            $package = $serviceUser->package;
            if (!$package) {
                continue;
            }

            $config = $serviceUser->config_account ?? [];
            $topUpAmount = 0.0;
            if (is_array($config)) {
                $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
            }

            $openFee = (float) $package->open_fee;
            $topUpFeePercent = (float) $package->top_up_fee;
            $supplierFeePercent = (float) ($package->supplier_fee_percent ?? 0);

            // Doanh thu = tổng số tiền khách hàng đã trả
            // = open_fee + top_up_amount + top_up_amount * top_up_fee%
            $itemRevenue = $openFee;
            if ($topUpAmount > 0) {
                $itemRevenue += $topUpAmount;
                if ($topUpFeePercent !== 0.0) {
                    $itemRevenue += $topUpAmount * $topUpFeePercent / 100;
                }
            }

            // Chi phí nhà cung cấp 
            $itemCost = 0.0;
            if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                $itemCost += $topUpAmount * $supplierFeePercent / 100;
            }

            $metaRevenue += $itemRevenue;
            $metaCost += $itemCost;
        }

        $stats['meta'] = [
            'revenue' => number_format($metaRevenue, 2, '.', ''),
            'cost' => number_format($metaCost, 2, '.', ''),
            'profit' => number_format($metaRevenue - $metaCost, 2, '.', ''),
        ];

        // Google
        $query = $this->serviceUserRepository->query()
            ->with('package:id,name,platform,open_fee,top_up_fee,supplier_fee_percent')
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::GOOGLE->value);
            });

            if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $googleServiceUsers = $query->get();

        $googleRevenue = 0.0;
        $googleCost = 0.0;
        foreach ($googleServiceUsers as $serviceUser) {
            $package = $serviceUser->package;
            if (!$package) {
                continue;
            }

            $config = $serviceUser->config_account ?? [];
            $topUpAmount = 0.0;
            if (is_array($config)) {
                $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
            }

            $openFee = (float) $package->open_fee;
            $topUpFeePercent = (float) $package->top_up_fee;
            $supplierFeePercent = (float) ($package->supplier_fee_percent ?? 0);

            // Doanh thu = tổng số tiền khách hàng đã trả
            // = open_fee + top_up_amount + top_up_amount * top_up_fee%
            $itemRevenue = $openFee;
            if ($topUpAmount > 0) {
                $itemRevenue += $topUpAmount;
                if ($topUpFeePercent !== 0.0) {
                    $itemRevenue += $topUpAmount * $topUpFeePercent / 100;
                }
            }

            // Chi phí nhà cung cấp (chỉ áp trên top_up_amount)
            $itemCost = 0.0;
            if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                $itemCost += $topUpAmount * $supplierFeePercent / 100;
            }

            $googleRevenue += $itemRevenue;
            $googleCost += $itemCost;
        }

        $stats['google'] = [
            'revenue' => number_format($googleRevenue, 2, '.', ''),
            'cost' => number_format($googleCost, 2, '.', ''),
            'profit' => number_format($googleRevenue - $googleCost, 2, '.', ''),
        ];

        return $stats;
    }

    /**
     * Tính lợi nhuận tổng theo thời gian (theo ngày/tuần/tháng)
     * 
     * Sử dụng cùng công thức margin dịch vụ như getProfitByPlatform:
     * - Doanh thu mỗi order = open_fee + top_up_amount + top_up_amount * top_up_fee%
     * - Chi phí mỗi order   = top_up_amount * supplier_fee_percent%
     * - Lợi nhuận           = Doanh thu - Chi phí
     */
    public function getProfitOverTime(string $groupBy = 'day', ?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
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

            // Mặc định 30 ngày gần nhất
            if (!$startDate || !$endDate) {
                $endDate = Carbon::today()->endOfDay();
                $startDate = $endDate->copy()->subDays(29)->startOfDay();
            }

            // Lấy toàn bộ service_users trong khoảng thời gian
            $query = $this->serviceUserRepository->query()
                ->with('package:id,name,platform,open_fee,top_up_fee,supplier_fee_percent')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('package');

            if (!empty($customerIds)) {
                $query->whereIn('user_id', $customerIds);
            }

            $serviceUsers = $query->get();

            // Gom nhóm theo period
            $buckets = [];

            foreach ($serviceUsers as $serviceUser) {
                $package = $serviceUser->package;
                if (!$package) {
                    continue;
                }

                $createdAt = $serviceUser->created_at instanceof Carbon
                    ? $serviceUser->created_at
                    : Carbon::parse($serviceUser->created_at);

                $period = match ($groupBy) {
                    'week' => $createdAt->isoWeekYear() . '-W' . str_pad((string) $createdAt->isoWeek(), 2, '0', STR_PAD_LEFT),
                    'month' => $createdAt->format('Y-m'),
                    default => $createdAt->format('Y-m-d'),
                };

                if (!isset($buckets[$period])) {
                    $buckets[$period] = [
                        'revenue' => 0.0,
                        'cost' => 0.0,
                    ];
                }

                $config = $serviceUser->config_account ?? [];
                $topUpAmount = 0.0;
                if (is_array($config)) {
                    $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
                }

                $openFee = (float) $package->open_fee;
                $topUpFeePercent = (float) $package->top_up_fee;
                $supplierFeePercent = (float) ($package->supplier_fee_percent ?? 0);

                // Doanh thu = tổng số tiền khách hàng đã trả
                // = open_fee + top_up_amount + top_up_amount * top_up_fee%
                $itemRevenue = $openFee;
                if ($topUpAmount > 0) {
                    $itemRevenue += $topUpAmount;
                    if ($topUpFeePercent !== 0.0) {
                        $itemRevenue += $topUpAmount * $topUpFeePercent / 100;
                    }
                }

                $itemCost = 0.0;
                if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                    $itemCost += $topUpAmount * $supplierFeePercent / 100;
                }

                $buckets[$period]['revenue'] += $itemRevenue;
                $buckets[$period]['cost'] += $itemCost;
            }

            ksort($buckets);

            $result = [];
            foreach ($buckets as $period => $values) {
                $revenue = (float) $values['revenue'];
                $cost = (float) $values['cost'];
                $profit = $revenue - $cost;
                $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $result[] = [
                    'period' => $period,
                    'revenue' => number_format($revenue, 2, '.', ''),
                    'cost' => number_format($cost, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'profit_margin' => number_format($profitMargin, 2, '.', ''),
                ];
            }

            return ServiceReturn::success(data: $result);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ProfitService@getProfitOverTime error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tính lợi nhuận theo BM/MCC
     * 
     */
    public function getProfitByBmMcc(?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Admin mới được xem
            if ($user->role !== UserRole::ADMIN->value) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Mặc định 30 ngày gần nhất
            if (!$startDate || !$endDate) {
                $endDate = Carbon::today()->endOfDay();
                $startDate = $endDate->copy()->subDays(29)->startOfDay();
            }

            // Lấy tất cả service_users active
            $serviceUsers = $this->serviceUserRepository->query()
                ->with(['user:id,name,username,email', 'package:id,platform,name,open_fee,top_up_fee,supplier_fee_percent'])
                ->where('status', \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value)
                ->whereHas('package')
                ->get();

            $bmMccMap = [];

            foreach ($serviceUsers as $serviceUser) {
                $config = $serviceUser->config_account ?? [];
                $platform = $serviceUser->package->platform ?? null;
                $userId = $serviceUser->user_id;

                if (!$platform) {
                    continue;
                }

                // Tính doanh thu/chi phí cho service_user này
                $topUpAmount = 0.0;
                if (is_array($config)) {
                    $topUpAmount = (float) ($config['top_up_amount'] ?? 0);
                }

                $openFee = (float) $serviceUser->package->open_fee;
                $topUpFeePercent = (float) $serviceUser->package->top_up_fee;
                $supplierFeePercent = (float) ($serviceUser->package->supplier_fee_percent ?? 0);

                // Doanh thu = tổng số tiền khách hàng đã trả
                // = open_fee + top_up_amount + top_up_amount * top_up_fee%
                $itemRevenue = $openFee;
                if ($topUpAmount > 0) {
                    $itemRevenue += $topUpAmount; // Số tiền nạp vào tài khoản quảng cáo
                    if ($topUpFeePercent !== 0.0) {
                        $itemRevenue += $topUpAmount * $topUpFeePercent / 100; // Phí dịch vụ top-up
                    }
                }

                $itemCost = 0.0;
                if ($topUpAmount > 0 && $supplierFeePercent > 0.0) {
                    $itemCost += $topUpAmount * $supplierFeePercent / 100;
                }

                $bmIds = [];
                if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                    foreach ($config['accounts'] as $account) {
                        if (isset($account['bm_ids']) && is_array($account['bm_ids'])) {
                            $bmIds = array_merge($bmIds, array_filter($account['bm_ids']));
                        }
                        if (isset($account['mcc_id']) && !empty($account['mcc_id'])) {
                            $bmIds[] = $account['mcc_id'];
                        }
                    }
                }

                // Nếu không có BM/MCC ID, dùng user_id làm key
                if (empty($bmIds)) {
                    $bmIds = ['user_' . $userId];
                }

                foreach ($bmIds as $bmId) {
                    $key = $platform . '_' . $bmId;
                    
                    if (!isset($bmMccMap[$key])) {
                        $bmMccMap[$key] = [
                            'bm_mcc_id' => $bmId,
                            'platform' => $platform,
                            'platform_name' => $platform === PlatformType::META->value ? 'Facebook Ads' : 'Google Ads',
                            'user_ids' => [],
                            'service_user_ids' => [],
                            'revenue' => 0.0,
                            'cost' => 0.0,
                        ];
                    }

                    if (!in_array($userId, $bmMccMap[$key]['user_ids'])) {
                        $bmMccMap[$key]['user_ids'][] = $userId;
                    }
                    if (!in_array($serviceUser->id, $bmMccMap[$key]['service_user_ids'])) {
                        $bmMccMap[$key]['service_user_ids'][] = $serviceUser->id;
                    }

                    $bmMccMap[$key]['revenue'] += $itemRevenue;
                    $bmMccMap[$key]['cost'] += $itemCost;
                }
            }

            $result = [];

            foreach ($bmMccMap as $key => $bmMcc) {
                $revenue = (float) ($bmMcc['revenue'] ?? 0);
                $cost = (float) ($bmMcc['cost'] ?? 0);
                $profit = $revenue - $cost;
                $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                // Lấy thông tin user đầu tiên (có thể cải thiện sau)
                $firstUserId = $bmMcc['user_ids'][0] ?? null;
                $userInfo = null;
                if ($firstUserId) {
                    $user = DB::table('users')
                        ->where('id', $firstUserId)
                        ->select('id', 'name', 'username', 'email')
                        ->first();
                    if ($user) {
                        $userInfo = [
                            'id' => $user->id,
                            'name' => $user->name ?? $user->username ?? 'Unknown',
                            'email' => $user->email ?? '',
                        ];
                    }
                }

                // Log tổng hợp theo BM/MCC
                Logging::error('ProfitService@getProfitByBmMcc summary', [
                    'bm_mcc_id' => $bmMcc['bm_mcc_id'],
                    'platform' => $bmMcc['platform'],
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'profit_margin' => $profitMargin,
                    'user_ids' => $bmMcc['user_ids'],
                    'service_user_ids' => $bmMcc['service_user_ids'],
                ]);

                $result[] = [
                    'bm_mcc_id' => $bmMcc['bm_mcc_id'],
                    'platform' => $bmMcc['platform'],
                    'platform_name' => $bmMcc['platform_name'],
                    'user' => $userInfo,
                    'user_count' => count($bmMcc['user_ids']),
                    'service_user_count' => count($bmMcc['service_user_ids']),
                    'revenue' => number_format($revenue, 2, '.', ''),
                    'cost' => number_format($cost, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'profit_margin' => number_format($profitMargin, 2, '.', ''),
                ];
            }

            // Sắp xếp theo lợi nhuận giảm dần
            usort($result, function ($a, $b) {
                return (float) $b['profit'] <=> (float) $a['profit'];
            });

            return ServiceReturn::success(data: $result);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ProfitService@getProfitByBmMcc error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}

