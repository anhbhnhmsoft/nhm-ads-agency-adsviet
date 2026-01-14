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

                // Tính doanh thu (revenue) từ các giao dịch admin nhận tiền:
                $revenueDepositQuery = $this->walletTransactionRepository->query()
                    ->whereHas('wallet', function ($q) use ($customerId) {
                        $q->where('user_id', $customerId);
                    })
                    ->where('type', WalletTransactionType::DEPOSIT->value)
                    ->where('status', WalletTransactionStatus::COMPLETED->value);

                $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                    ->whereHas('wallet', function ($q) use ($customerId) {
                        $q->where('user_id', $customerId);
                    })
                    ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                    ->where('status', WalletTransactionStatus::COMPLETED->value);

                if ($startDate && $endDate) {
                    $revenueDepositQuery->whereBetween('created_at', [$startDate, $endDate]);
                    $revenuePurchaseQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                $revenueDeposit = (float) $revenueDepositQuery->sum('amount') ?? 0;
                $revenuePurchaseRaw = (float) $revenuePurchaseQuery->sum('amount') ?? 0;
                $revenuePurchase = abs($revenuePurchaseRaw);
                $revenue = $revenueDeposit + $revenuePurchase;

                // Tính chi phí (cost) từ các giao dịch admin chi tiền:
                $costQuery = $this->walletTransactionRepository->query()
                    ->whereHas('wallet', function ($q) use ($customerId) {
                        $q->where('user_id', $customerId);
                    })
                    ->whereIn('type', [
                        WalletTransactionType::WITHDRAW->value,
                        WalletTransactionType::REFUND->value,
                    ])
                    ->where('status', WalletTransactionStatus::COMPLETED->value);

                if ($startDate && $endDate) {
                    $costQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                // WITHDRAW và REFUND có amount là số âm trong DB, lấy giá trị tuyệt đối
                $costRaw = (float) $costQuery->sum('amount') ?? 0;
                $cost = abs($costRaw);

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

                    // Tính doanh thu từ các giao dịch admin nhận tiền: DEPOSIT + SERVICE_PURCHASE
                    $revenueDepositQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->where('type', WalletTransactionType::DEPOSIT->value)
                        ->where('status', WalletTransactionStatus::COMPLETED->value);

                    $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                        ->where('status', WalletTransactionStatus::COMPLETED->value);

                    if ($startDate && $endDate) {
                        $revenueDepositQuery->whereBetween('created_at', [$startDate, $endDate]);
                        $revenuePurchaseQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }

                    $revenueDeposit = (float) $revenueDepositQuery->sum('amount') ?? 0;
                    $revenuePurchaseRaw = (float) $revenuePurchaseQuery->sum('amount') ?? 0;
                    $revenuePurchase = abs($revenuePurchaseRaw);
                    $revenue += $revenueDeposit + $revenuePurchase;

                    // Tính chi phí từ các giao dịch admin chi tiền: WITHDRAW + REFUND
                    $costQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->whereIn('type', [
                            WalletTransactionType::WITHDRAW->value,
                            WalletTransactionType::REFUND->value,
                        ])
                        ->where('status', WalletTransactionStatus::COMPLETED->value);

                    if ($startDate && $endDate) {
                        $costQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }

                    $costRaw = (float) $costQuery->sum('amount') ?? 0;
                    $cost += abs($costRaw);
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
            // Tính doanh thu
            $revenueDepositQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->where('type', WalletTransactionType::DEPOSIT->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            if ($startDate && $endDate) {
                $revenueDepositQuery->whereBetween('created_at', [$startDate, $endDate]);
                $revenuePurchaseQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $revenueDeposit = (float) $revenueDepositQuery->sum('amount') ?? 0;
            $revenuePurchaseRaw = (float) $revenuePurchaseQuery->sum('amount') ?? 0;
            $revenuePurchase = abs($revenuePurchaseRaw);
            $metaRevenue += $revenueDeposit + $revenuePurchase;

            // Tính chi phí
            $costQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->whereIn('type', [
                    WalletTransactionType::WITHDRAW->value,
                    WalletTransactionType::REFUND->value,
                ])
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            if ($startDate && $endDate) {
                $costQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $costRaw = (float) $costQuery->sum('amount') ?? 0;
            $metaCost += abs($costRaw);
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
            // Tính doanh thu
            $revenueDepositQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->where('type', WalletTransactionType::DEPOSIT->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            if ($startDate && $endDate) {
                $revenueDepositQuery->whereBetween('created_at', [$startDate, $endDate]);
                $revenuePurchaseQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $revenueDeposit = (float) $revenueDepositQuery->sum('amount') ?? 0;
            $revenuePurchaseRaw = (float) $revenuePurchaseQuery->sum('amount') ?? 0;
            $revenuePurchase = abs($revenuePurchaseRaw);
            $googleRevenue += $revenueDeposit + $revenuePurchase;

            // Tính chi phí
            $costQuery = $this->walletTransactionRepository->query()
                ->whereHas('wallet', function ($q) use ($customerId) {
                    $q->where('user_id', $customerId);
                })
                ->whereIn('type', [
                    WalletTransactionType::WITHDRAW->value,
                    WalletTransactionType::REFUND->value,
                ])
                ->where('status', WalletTransactionStatus::COMPLETED->value);

            if ($startDate && $endDate) {
                $costQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $costRaw = (float) $costQuery->sum('amount') ?? 0;
            $googleCost += abs($costRaw);
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

            $dateFormat = match($groupBy) {
                'week' => 'IYYY-IW',
                'month' => 'YYYY-MM',
                default => 'YYYY-MM-DD',
            };

            // Tính doanh thu theo thời gian
            $revenueDepositQuery = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::DEPOSIT->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if (!empty($customerIds)) {
                $revenueDepositQuery->whereHas('wallet', function ($q) use ($customerIds) {
                    $q->whereIn('user_id', $customerIds);
                });
                $revenuePurchaseQuery->whereHas('wallet', function ($q) use ($customerIds) {
                    $q->whereIn('user_id', $customerIds);
                });
            }

            $revenuesDeposit = $revenueDepositQuery
                ->selectRaw("to_char(created_at, '{$dateFormat}') as period, SUM(amount) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $revenuesPurchase = $revenuePurchaseQuery
                ->selectRaw("to_char(created_at, '{$dateFormat}') as period, SUM(ABS(amount)) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $allPeriods = $revenuesDeposit->keys()->merge($revenuesPurchase->keys())->unique();
            $revenues = collect();
            
            foreach ($allPeriods as $period) {
                $depositTotal = (float) ($revenuesDeposit->get($period)->total ?? 0);
                $purchaseTotal = (float) ($revenuesPurchase->get($period)->total ?? 0);
                $revenues->put($period, (object) [
                    'period' => $period,
                    'total' => $depositTotal + $purchaseTotal,
                ]);
            }

            // Tính chi phí theo thời gian
            $costQuery = $this->walletTransactionRepository->query()
                ->whereIn('type', [
                    WalletTransactionType::WITHDRAW->value,
                    WalletTransactionType::REFUND->value,
                ])
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if (!empty($customerIds)) {
                $costQuery->whereHas('wallet', function ($q) use ($customerIds) {
                    $q->whereIn('user_id', $customerIds);
                });
            }

            $costs = $costQuery
                ->selectRaw("to_char(created_at, '{$dateFormat}') as period, SUM(ABS(amount)) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            // Merge và tính lợi nhuận
            $allPeriods = $revenues->keys()->merge($costs->keys())->unique()->sort();
            $result = [];

            foreach ($allPeriods as $period) {
                $revenue = (float) ($revenues->get($period)->total ?? 0);
                // Cost đã được tính với ABS trong SQL
                $cost = (float) ($costs->get($period)->total ?? 0);
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
                ->with(['user:id,name,username,email', 'package:id,platform,name'])
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
                        ];
                    }

                    if (!in_array($userId, $bmMccMap[$key]['user_ids'])) {
                        $bmMccMap[$key]['user_ids'][] = $userId;
                    }
                    if (!in_array($serviceUser->id, $bmMccMap[$key]['service_user_ids'])) {
                        $bmMccMap[$key]['service_user_ids'][] = $serviceUser->id;
                    }
                }
            }

            $result = [];

            foreach ($bmMccMap as $key => $bmMcc) {
                $revenue = 0;
                $cost = 0;

                // Tính doanh thu từ các user liên quan
                foreach ($bmMcc['user_ids'] as $userId) {
                    $revenueDepositQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->where('type', WalletTransactionType::DEPOSIT->value)
                        ->where('status', WalletTransactionStatus::COMPLETED->value)
                        ->whereBetween('created_at', [$startDate, $endDate]);

                    $revenuePurchaseQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
                        ->where('status', WalletTransactionStatus::COMPLETED->value)
                        ->whereBetween('created_at', [$startDate, $endDate]);

                    $revenueDeposit = (float) $revenueDepositQuery->sum('amount') ?? 0;
                    $revenuePurchaseRaw = (float) $revenuePurchaseQuery->sum('amount') ?? 0;
                    $revenuePurchase = abs($revenuePurchaseRaw);
                    $revenue += $revenueDeposit + $revenuePurchase;

                    // Tính chi phí
                    $costQuery = $this->walletTransactionRepository->query()
                        ->whereHas('wallet', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->whereIn('type', [
                            WalletTransactionType::WITHDRAW->value,
                            WalletTransactionType::REFUND->value,
                        ])
                        ->where('status', WalletTransactionStatus::COMPLETED->value)
                        ->whereBetween('created_at', [$startDate, $endDate]);

                    $costRaw = (float) $costQuery->sum('amount') ?? 0;
                    $cost += abs($costRaw);
                }

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

