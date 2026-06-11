<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Service\PlatformSettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserWalletTransactionRepository $walletTransactionRepository,
        protected PlatformSettingService $platformSettingService,
    ) {
    }

    /**
     * Áp dụng bộ lọc BM/MCC từ PlatformSetting
     */
    protected function applyPlatformSettingFilter($query, int $platform): void
    {
        $settingId = $platform === PlatformType::META->value 
            ? session('active_meta_setting_id') 
            : session('active_google_setting_id');

        if ($settingId) {
            $settingResult = $this->platformSettingService->find($settingId);
            if ($settingResult->isSuccess()) {
                $setting = $settingResult->getData();
                $config = $setting->config ?? [];
                
                if ($platform === PlatformType::META->value && ($bmId = $this->platformSettingService->getMetaScopedBusinessManagerId($config))) {
                    $query->where(function ($q) use ($bmId) {
                        $q->whereJsonContains('config_account->business_manager_id', $bmId)
                          ->orWhereJsonContains('config_account->bm_id', $bmId)
                          ->orWhereJsonContains('config_account->child_bm_id', $bmId);
                    });
                } elseif ($platform === PlatformType::GOOGLE->value && isset($config['login_customer_id'])) {
                    $mccId = (string) $config['login_customer_id'];
                    $query->where(function ($q) use ($mccId) {
                        $q->whereJsonContains('config_account->login_customer_id', $mccId)
                          ->orWhereJsonContains('config_account->customer_manager_id', $mccId);
                    });
                }
            }
        }
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
     * @param int|null $platform
     * @return ServiceReturn
     */
    public function getProfitByCustomer(?int $customerId = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?int $platform = null): ServiceReturn
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
                    ->with($this->profitRelations())
                    ->where('user_id', $customerId)
                    ->whereHas('package');

                if ($platform) {
                    $query->whereHas('package', function ($q) use ($platform) {
                        $q->where('platform', $platform);
                    });
                }

                $serviceUsers = $query->get();

                $revenue = 0.0;
                $cost = 0.0;

                foreach ($serviceUsers as $serviceUser) {
                    $components = $this->calculateServiceUserProfitComponents($serviceUser, $startDate, $endDate);

                    $revenue += $components['revenue'];
                    $cost += $components['cost'];
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
            //   + Chi phí (cost_item) = supplier_open_fee + top_up_amount * supplier_fee_percent%
            //     (Chi phí mở tài khoản của nhà cung cấp + chi phí nhà cung cấp tính trên số tiền nạp)
            //   + Lợi nhuận (profit_item) = revenue_item - cost_item
            //   Trong đó top_up_amount được lưu trong config_account của service_users.

            $platforms = $platform ? [$platform] : [PlatformType::META->value, PlatformType::GOOGLE->value];
            $result = [];

            foreach ($platforms as $platformType) {
                // Lấy service_users theo platform
                $query = $this->serviceUserRepository->query()
                    ->with($this->profitRelations())
                    ->whereHas('package', function ($q) use ($platformType) {
                        $q->where('platform', $platformType);
                    });

                if (!empty($customerIds)) {
                    $query->whereIn('user_id', $customerIds);
                }

                $this->applyPlatformSettingFilter($query, $platformType);

                $serviceUsers = $query->get();

                $revenue = 0.0;
                $cost = 0.0;

                foreach ($serviceUsers as $serviceUser) {
                    $components = $this->calculateServiceUserProfitComponents($serviceUser, $startDate, $endDate);

                    $revenue += $components['revenue'];
                    $cost += $components['cost'];
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
            ->with($this->profitRelations())
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::META->value);
            });

        $metaServiceUsers = $query->get();

        $metaRevenue = 0.0;
        $metaCost = 0.0;
        foreach ($metaServiceUsers as $serviceUser) {
            $components = $this->calculateServiceUserProfitComponents($serviceUser, $startDate, $endDate);

            $metaRevenue += $components['revenue'];
            $metaCost += $components['cost'];
        }

        $stats['meta'] = [
            'revenue' => number_format($metaRevenue, 2, '.', ''),
            'cost' => number_format($metaCost, 2, '.', ''),
            'profit' => number_format($metaRevenue - $metaCost, 2, '.', ''),
        ];

        // Google
        $query = $this->serviceUserRepository->query()
            ->with($this->profitRelations())
            ->where('user_id', $customerId)
            ->whereHas('package', function ($q) {
                $q->where('platform', PlatformType::GOOGLE->value);
            });

        $googleServiceUsers = $query->get();

        $googleRevenue = 0.0;
        $googleCost = 0.0;
        foreach ($googleServiceUsers as $serviceUser) {
            $components = $this->calculateServiceUserProfitComponents($serviceUser, $startDate, $endDate);

            $googleRevenue += $components['revenue'];
            $googleCost += $components['cost'];
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
     * @param int|null $platform
     */
    public function getProfitOverTime(string $groupBy = 'day', ?Carbon $startDate = null, ?Carbon $endDate = null, ?int $platform = null): ServiceReturn
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
                ->with($this->profitRelations())
                ->where('created_at', '<=', $endDate)
                ->whereHas('package');

            if ($platform) {
                $query->whereHas('package', function ($q) use ($platform) {
                    $q->where('platform', $platform);
                });
            }

            if (!empty($customerIds)) {
                $query->whereIn('user_id', $customerIds);
            }

            if ($platform) {
                $this->applyPlatformSettingFilter($query, $platform);
            } else {
                // Nếu lấy tất cả platform, lọc Meta và Google theo session nếu có
                $this->applyPlatformSettingFilter($query, PlatformType::META->value);
                $this->applyPlatformSettingFilter($query, PlatformType::GOOGLE->value);
            }

            $serviceUsers = $query->get();

            // Gom nhóm theo period
            $buckets = [];

            foreach ($serviceUsers as $serviceUser) {
                $package = $serviceUser->package;
                if (!$package) {
                    continue;
                }

                $config = $this->serviceUserConfig($serviceUser);
                $billingSource = $this->resolveBillingSource($serviceUser, $config);
                $createdAt = $serviceUser->created_at instanceof Carbon
                    ? $serviceUser->created_at
                    : Carbon::parse($serviceUser->created_at);

                if ($this->serviceUserCreatedInRange($serviceUser, $startDate, $endDate)) {
                    $purchaseComponents = $this->calculateServiceUserPurchaseComponents($serviceUser, $billingSource, $config);
                    $this->addProfitToBucket(
                        $buckets,
                        $this->profitPeriod($createdAt, $groupBy),
                        $purchaseComponents['revenue'],
                        $purchaseComponents['cost']
                    );
                }

                $this->addAccountTopUpTransactionsToBuckets($buckets, $serviceUser, $billingSource, $groupBy, $startDate, $endDate);
                $this->addSpendingProfitToBuckets($buckets, $serviceUser, $billingSource, $groupBy, $startDate, $endDate);
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
     * @param int|null $platform
     */
    public function getProfitByBmMcc(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $platform = null): ServiceReturn
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
            $serviceUsersQuery = $this->serviceUserRepository->query()
                ->with(array_merge(['user:id,name,username,email'], $this->profitRelations()))
                ->where('status', \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value)
                ->whereHas('package');

            if ($platform) {
                $this->applyPlatformSettingFilter($serviceUsersQuery, $platform);
            } else {
                $this->applyPlatformSettingFilter($serviceUsersQuery, PlatformType::META->value);
                $this->applyPlatformSettingFilter($serviceUsersQuery, PlatformType::GOOGLE->value);
            }

            $serviceUsers = $serviceUsersQuery->get();

            $bmMccMap = [];

            foreach ($serviceUsers as $serviceUser) {
                $config = $serviceUser->config_account ?? [];
                $platform = $serviceUser->package->platform ?? null;
                $userId = $serviceUser->user_id;

                if (!$platform) {
                    continue;
                }

                $components = $this->calculateServiceUserProfitComponents($serviceUser, $startDate, $endDate);
                $itemRevenue = $components['revenue'];
                $itemCost = $components['cost'];

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

    private function profitRelations(): array
    {
        return [
            'package:id,name,platform,payment_type,billing_source,open_fee,top_up_fee,spending_fee,supplier_fee_percent,supplier_id',
            'package.supplier:id,name,open_fee,postpay_fee,supplier_fee_percent',
        ];
    }

    private function calculateServiceUserProfitComponents($serviceUser, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $package = $serviceUser->package ?? null;
        if (!$package) {
            return ['revenue' => 0.0, 'cost' => 0.0];
        }

        $config = $this->serviceUserConfig($serviceUser);
        $billingSource = $this->resolveBillingSource($serviceUser, $config);
        $revenue = 0.0;
        $cost = 0.0;

        if ($this->serviceUserCreatedInRange($serviceUser, $startDate, $endDate)) {
            $purchaseComponents = $this->calculateServiceUserPurchaseComponents($serviceUser, $billingSource, $config);

            $revenue += $purchaseComponents['revenue'];
            $cost += $purchaseComponents['cost'];
        }

        $accountTopUpComponents = $this->calculateAccountTopUpTransactionComponents(
            $serviceUser,
            $billingSource,
            $startDate,
            $endDate
        );
        $revenue += $accountTopUpComponents['revenue'];
        $cost += $accountTopUpComponents['cost'];

        $spend = $this->getServiceUserSpend($serviceUser, $startDate, $endDate);
        if ($spend > 0 && $this->shouldChargeSpendingFee($serviceUser, $billingSource)) {
            $revenue += $this->calculatePercentAmount($spend, (float) ($package->spending_fee ?? 0));
        }

        if ($spend > 0 && $this->shouldApplySupplierPostpayCost($serviceUser)) {
            $cost += $this->calculatePercentAmount($spend, (float) ($package->supplier?->postpay_fee ?? 0));
        }

        return [
            'revenue' => $revenue,
            'cost' => $cost,
        ];
    }

    private function calculateServiceUserPurchaseComponents($serviceUser, string $billingSource, array $config): array
    {
        $package = $serviceUser->package;
        $accountsCount = $this->configuredAccountsCount($config);
        $topUpAmount = $this->configuredTopUpAmount($config);

        $revenue = (float) ($package->open_fee ?? 0) * $accountsCount;
        if ($topUpAmount > 0) {
            $revenue += $topUpAmount + $this->calculatePercentAmount($topUpAmount, (float) ($package->top_up_fee ?? 0));
        }

        $cost = 0.0;
        $supplier = $package->supplier ?? null;
        if ($supplier) {
            $cost += (float) ($supplier->open_fee ?? 0) * $accountsCount;
            if ($topUpAmount > 0 && $this->usesTopUpBilling($billingSource)) {
                $cost += $this->calculatePercentAmount($topUpAmount, $this->resolveSupplierFeePercent($package));
            }
        }

        return [
            'revenue' => $revenue,
            'cost' => $cost,
        ];
    }

    private function calculateAccountTopUpTransactionComponents(
        $serviceUser,
        string $defaultBillingSource,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = $this->walletTransactionRepository->query()
            ->whereIn('type', [
                WalletTransactionType::ACCOUNT_TOP_UP_GOOGLE->value,
                WalletTransactionType::ACCOUNT_TOP_UP_META->value,
            ])
            ->whereNotIn('status', [
                WalletTransactionStatus::REJECTED->value,
                WalletTransactionStatus::CANCELLED->value,
            ])
            ->where('withdraw_info->service_user_id', (string) $serviceUser->id);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ]);
        }

        $revenue = 0.0;
        $cost = 0.0;
        $supplierFeePercent = $this->resolveSupplierFeePercent($serviceUser->package);

        foreach ($query->get(['amount', 'withdraw_info']) as $transaction) {
            $metadata = is_array($transaction->withdraw_info) ? $transaction->withdraw_info : [];
            $topUpAmount = (float) ($metadata['top_up_amount'] ?? 0);
            $feeAmount = (float) ($metadata['fee_amount'] ?? 0);
            $totalChargeAmount = (float) ($metadata['total_charge_amount'] ?? abs((float) $transaction->amount));
            $billingSource = $metadata['billing_source'] ?? $defaultBillingSource;

            if ($topUpAmount <= 0) {
                $topUpAmount = max(0.0, $totalChargeAmount - $feeAmount);
            }

            $revenue += $totalChargeAmount;

            if ($topUpAmount > 0 && $this->usesTopUpBilling($billingSource)) {
                $cost += $this->calculatePercentAmount($topUpAmount, $supplierFeePercent);
            }
        }

        return [
            'revenue' => $revenue,
            'cost' => $cost,
        ];
    }

    private function addAccountTopUpTransactionsToBuckets(
        array &$buckets,
        $serviceUser,
        string $defaultBillingSource,
        string $groupBy,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $query = $this->walletTransactionRepository->query()
            ->whereIn('type', [
                WalletTransactionType::ACCOUNT_TOP_UP_GOOGLE->value,
                WalletTransactionType::ACCOUNT_TOP_UP_META->value,
            ])
            ->whereNotIn('status', [
                WalletTransactionStatus::REJECTED->value,
                WalletTransactionStatus::CANCELLED->value,
            ])
            ->where('withdraw_info->service_user_id', (string) $serviceUser->id)
            ->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ]);

        $supplierFeePercent = $this->resolveSupplierFeePercent($serviceUser->package);

        foreach ($query->get(['amount', 'withdraw_info', 'created_at']) as $transaction) {
            $metadata = is_array($transaction->withdraw_info) ? $transaction->withdraw_info : [];
            $topUpAmount = (float) ($metadata['top_up_amount'] ?? 0);
            $feeAmount = (float) ($metadata['fee_amount'] ?? 0);
            $totalChargeAmount = (float) ($metadata['total_charge_amount'] ?? abs((float) $transaction->amount));
            $billingSource = $metadata['billing_source'] ?? $defaultBillingSource;

            if ($topUpAmount <= 0) {
                $topUpAmount = max(0.0, $totalChargeAmount - $feeAmount);
            }

            $cost = 0.0;
            if ($topUpAmount > 0 && $this->usesTopUpBilling($billingSource)) {
                $cost = $this->calculatePercentAmount($topUpAmount, $supplierFeePercent);
            }

            $createdAt = $transaction->created_at instanceof Carbon
                ? $transaction->created_at
                : Carbon::parse($transaction->created_at);

            $this->addProfitToBucket(
                $buckets,
                $this->profitPeriod($createdAt, $groupBy),
                $totalChargeAmount,
                $cost
            );
        }
    }

    private function addSpendingProfitToBuckets(
        array &$buckets,
        $serviceUser,
        string $billingSource,
        string $groupBy,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $shouldChargeSpendingFee = $this->shouldChargeSpendingFee($serviceUser, $billingSource);
        $shouldApplySupplierCost = $this->shouldApplySupplierPostpayCost($serviceUser);
        if (!$shouldChargeSpendingFee && !$shouldApplySupplierCost) {
            return;
        }

        $spendingFeePercent = (float) ($serviceUser->package?->spending_fee ?? 0);
        $supplierPostpayFeePercent = (float) ($serviceUser->package?->supplier?->postpay_fee ?? 0);

        foreach ($this->serviceUserInsightTables($serviceUser) as $table) {
            $rows = DB::table($table)
                ->selectRaw('date, SUM(CAST(spend AS DECIMAL(18,4))) as total_spend')
                ->where('service_user_id', (string) $serviceUser->id)
                ->whereNull('deleted_at')
                ->whereBetween('date', [
                    $startDate->copy()->toDateString(),
                    $endDate->copy()->toDateString(),
                ])
                ->groupBy('date')
                ->get();

            foreach ($rows as $row) {
                $spend = (float) ($row->total_spend ?? 0);
                if ($spend <= 0) {
                    continue;
                }

                $revenue = $shouldChargeSpendingFee
                    ? $this->calculatePercentAmount($spend, $spendingFeePercent)
                    : 0.0;
                $cost = $shouldApplySupplierCost
                    ? $this->calculatePercentAmount($spend, $supplierPostpayFeePercent)
                    : 0.0;

                $this->addProfitToBucket(
                    $buckets,
                    $this->profitPeriod(Carbon::parse($row->date), $groupBy),
                    $revenue,
                    $cost
                );
            }
        }
    }

    private function getServiceUserSpend($serviceUser, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $spend = 0.0;
        foreach ($this->serviceUserInsightTables($serviceUser) as $table) {
            $query = DB::table($table)
                ->where('service_user_id', (string) $serviceUser->id)
                ->whereNull('deleted_at');

            if ($startDate && $endDate) {
                $query->whereBetween('date', [
                    $startDate->copy()->toDateString(),
                    $endDate->copy()->toDateString(),
                ]);
            }

            $spend += (float) $query->sum(DB::raw('CAST(spend AS DECIMAL(18,4))'));
        }

        return $spend;
    }

    private function serviceUserInsightTables($serviceUser): array
    {
        return match ((int) ($serviceUser->package?->platform ?? 0)) {
            PlatformType::META->value => ['meta_ads_account_insights'],
            PlatformType::GOOGLE->value => ['google_ads_account_insights'],
            default => ['meta_ads_account_insights', 'google_ads_account_insights'],
        };
    }

    private function profitPeriod(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => $date->isoWeekYear() . '-W' . str_pad((string) $date->isoWeek(), 2, '0', STR_PAD_LEFT),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    private function addProfitToBucket(array &$buckets, string $period, float $revenue, float $cost): void
    {
        if (!isset($buckets[$period])) {
            $buckets[$period] = [
                'revenue' => 0.0,
                'cost' => 0.0,
            ];
        }

        $buckets[$period]['revenue'] += $revenue;
        $buckets[$period]['cost'] += $cost;
    }

    private function serviceUserConfig($serviceUser): array
    {
        return is_array($serviceUser->config_account ?? null) ? $serviceUser->config_account : [];
    }

    private function serviceUserCreatedInRange($serviceUser, ?Carbon $startDate = null, ?Carbon $endDate = null): bool
    {
        if (!$startDate || !$endDate) {
            return true;
        }

        $createdAt = $serviceUser->created_at instanceof Carbon
            ? $serviceUser->created_at
            : Carbon::parse($serviceUser->created_at);

        return $createdAt->betweenIncluded(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay()
        );
    }

    private function configuredAccountsCount(array $config): int
    {
        return isset($config['accounts']) && is_array($config['accounts']) && count($config['accounts']) > 0
            ? count($config['accounts'])
            : 1;
    }

    private function configuredTopUpAmount(array $config): float
    {
        return isset($config['top_up_amount']) && is_numeric($config['top_up_amount'])
            ? max(0.0, (float) $config['top_up_amount'])
            : 0.0;
    }

    private function resolveBillingSource($serviceUser, array $config): string
    {
        $source = $serviceUser->package?->billing_source
            ?? $config['billing_source']
            ?? $config['payment_source']
            ?? null;

        if (in_array($source, AccountBillingSource::getValues(), true)) {
            return $source;
        }

        if (!empty($serviceUser->package?->supplier_id)) {
            return AccountBillingSource::SUPPLIER_CREDIT_LINE->value;
        }

        if ($this->resolvePaymentType($serviceUser) === ServicePackagePaymentType::POSTPAY->value) {
            return AccountBillingSource::CUSTOMER_CARD->value;
        }

        return AccountBillingSource::ADVIET_CARD->value;
    }

    private function resolvePaymentType($serviceUser): string
    {
        $config = $this->serviceUserConfig($serviceUser);
        $paymentType = $config['payment_type'] ?? $serviceUser->package?->payment_type ?? ServicePackagePaymentType::PREPAY->value;

        return in_array($paymentType, ServicePackagePaymentType::getValues(), true)
            ? $paymentType
            : ServicePackagePaymentType::PREPAY->value;
    }

    private function usesTopUpBilling(string $billingSource): bool
    {
        return in_array($billingSource, [
            AccountBillingSource::ADVIET_CARD->value,
            AccountBillingSource::SUPPLIER_CREDIT_LINE->value,
        ], true);
    }

    private function shouldChargeSpendingFee($serviceUser, string $billingSource): bool
    {
        $spendingFeePercent = (float) ($serviceUser->package?->spending_fee ?? 0);
        if ($spendingFeePercent <= 0) {
            return false;
        }

        return $billingSource === AccountBillingSource::CUSTOMER_CARD->value
            || $this->resolvePaymentType($serviceUser) === ServicePackagePaymentType::POSTPAY->value;
    }

    private function shouldApplySupplierPostpayCost($serviceUser): bool
    {
        $supplierPostpayFeePercent = (float) ($serviceUser->package?->supplier?->postpay_fee ?? 0);

        return $supplierPostpayFeePercent > 0
            && $this->resolvePaymentType($serviceUser) === ServicePackagePaymentType::POSTPAY->value;
    }

    private function resolveSupplierFeePercent($package): float
    {
        $supplier = $package->supplier ?? null;
        if ($supplier && isset($supplier->supplier_fee_percent)) {
            return (float) $supplier->supplier_fee_percent;
        }

        return (float) ($package->supplier_fee_percent ?? 0);
    }

    private function calculatePercentAmount(float $amount, float $percent): float
    {
        return $percent > 0 ? $amount * $percent / 100 : 0.0;
    }
}
