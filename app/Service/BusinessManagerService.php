<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\ServicePackage\Meta\MetaAdsAccountStatus;
use App\Common\Constants\Google\GoogleCustomerStatus;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\GoogleAdsAccountInsightRepository;
use App\Repositories\UserReferralRepository;
use App\Core\Logging;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Repositories\PlatformSettingRepository;

class BusinessManagerService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected GoogleAccountRepository $googleAccountRepository,
        protected MetaAdsAccountInsightRepository $metaAdsAccountInsightRepository,
        protected GoogleAdsAccountInsightRepository $googleAdsAccountInsightRepository,
        protected UserReferralRepository $userReferralRepository,
        protected PlatformSettingRepository $platformSettingRepository,
    ) {
    }

    /**
     * Lấy danh sách Business Managers / MCC với pagination
     */
    public function getListBusinessManagers(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];

            // Xác định date range từ filter
            $dateStart = null;
            $dateEnd = null;
            if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
                $dateStart = Carbon::parse($filter['start_date'])->startOfDay();
                $dateEnd = Carbon::parse($filter['end_date'])->endOfDay();
            }

            // Lấy user hiện tại để check role
            $user = Auth::user();

            // Lấy tất cả service_users active
            // Đảm bảo load package để có platform
            $query = $this->serviceUserRepository->query()
                ->with(['user:id,name,username', 'package:id,platform'])
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->whereHas('package');

            // Nếu là customer, chỉ lấy service_users của chính họ
            if ($user && $user->role === UserRole::CUSTOMER->value) {
                $query->where('user_id', $user->id);
            } elseif ($user && $user->role === UserRole::AGENCY->value) {
                // Agency: lấy service_users của chính họ + service_users của các customer mà họ quản lý
                $managedCustomerIds = $this->userReferralRepository->query()
                    ->where('referrer_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('referred_id')
                    ->toArray();

                $userIds = array_merge([$user->id], $managedCustomerIds);
                $query->whereIn('user_id', $userIds);
            }

            $query = $this->serviceUserRepository->sortQuery($query, 'created_at', 'desc');

            // Filter theo platform nếu có
            if (!empty($filter['platform'])) {
                $query->whereHas('package', function ($q) use ($filter) {
                    $q->where('platform', (int) $filter['platform']);
                });
            }

            $serviceUsers = $query->get();

            // Danh sách tài khoản quảng cáo
            $accountsList = [];

            // Lấy accounts từ platform config
            if ($user && $user->role === UserRole::ADMIN->value) {
                $platformConfigAccounts = $this->getAccountsFromPlatformConfig($dateStart, $dateEnd, $filter['platform'] ?? null);
                $accountsList = array_merge($accountsList, $platformConfigAccounts);
            }

            // Tính thống kê tổng
            $stats = [
                'total_accounts' => 0,
                'active_accounts' => 0,
                'disabled_accounts' => 0,
                'by_platform' => [
                    PlatformType::META->value => [
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                    ],
                    PlatformType::GOOGLE->value => [
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                    ],
                ],
            ];

            foreach ($serviceUsers as $serviceUser) {
                $config = $serviceUser->config_account ?? [];
                $platform = $serviceUser->package->platform ?? null;

                // Bỏ qua nếu không có platform
                if (!$platform) {
                    continue;
                }

                // Lấy danh sách BM IDs từ config_account
                $bmIds = [];
                if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                    foreach ($config['accounts'] as $accountConfig) {
                        if (isset($accountConfig['bm_ids']) && is_array($accountConfig['bm_ids'])) {
                            $bmIds = array_merge($bmIds, array_filter($accountConfig['bm_ids'], fn($id) => !empty(trim($id ?? ''))));
                        }
                    }
                } else {
                    $bmId = $config['bm_id'] ?? null;
                    if ($bmId) {
                        $bmIds[] = $bmId;
                    }
                }

                if (empty($bmIds)) {
                    $bmIds = ['user_' . $serviceUser->user_id];
                }

                $ownerName = $serviceUser->user->name ?? $serviceUser->user->username ?? 'Unknown';

                // Lấy tất cả tài khoản quảng cáo theo platform
                if ($platform === PlatformType::META->value) {
                    $accounts = $this->metaAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->get();

                    foreach ($accounts as $account) {
                        // Tính spend cho từng account
                        if ($dateStart && $dateEnd) {
                            $spend = $this->metaAdsAccountInsightRepository->query()
                                ->where('meta_account_id', $account->id)
                                ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                                ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                                ->first();
                            $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                        } else {
                            $spendValue = (string) ($account->amount_spent ?? '0');
                        }

                        $balanceValue = (string) ($account->balance ?? '0');
                        $status = $account->account_status !== null ? (int) $account->account_status : null;
                        $isActive = $this->isAccountActive((int) $platform, $status);

                        $accountsList[] = [
                            'id' => (string) $account->account_id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'service_user_id' => (string) $serviceUser->id,
                            'bm_ids' => $bmIds,
                            'name' => $account->account_name ?? $config['display_name'] ?? $ownerName,
                            'platform' => $platform,
                            'owner_name' => $ownerName,
                            'owner_id' => $serviceUser->user_id,
                            'total_accounts' => 1,
                            'active_accounts' => $isActive ? 1 : 0,
                            'disabled_accounts' => $isActive ? 0 : 1,
                            'total_spend' => $spendValue,
                            'total_balance' => $balanceValue,
                            'currency' => $account->currency ?? 'USD',
                            'accounts' => [
                                ['currency' => $account->currency ?? 'USD'],
                            ],
                        ];

                    }
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $accounts = $this->googleAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->get();

                    foreach ($accounts as $account) {
                        // Tính spend từ insights nếu có date range
                        if ($dateStart && $dateEnd) {
                            $spend = $this->googleAdsAccountInsightRepository->query()
                                ->where('google_account_id', $account->id)
                                ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                                ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                                ->first();
                            $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                        } else {
                            // Google không có amount_spent trong account, để 0 hoặc tính từ insights tổng
                            $spendValue = '0';
                        }

                        $balanceValue = (string) ($account->balance ?? '0');
                        $status = $account->account_status !== null ? (int) $account->account_status : null;
                        $isActive = $this->isAccountActive((int) $platform, $status);

                        $accountsList[] = [
                            'id' => (string) $account->account_id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'service_user_id' => (string) $serviceUser->id,
                            'bm_ids' => $bmIds,
                            'name' => $account->account_name ?? $config['display_name'] ?? $ownerName,
                            'platform' => $platform,
                            'owner_name' => $ownerName,
                            'owner_id' => $serviceUser->user_id,
                            'total_accounts' => 1,
                            'active_accounts' => $isActive ? 1 : 0,
                            'disabled_accounts' => $isActive ? 0 : 1,
                            'total_spend' => $spendValue,
                            'total_balance' => $balanceValue,
                            'currency' => $account->currency ?? 'USD',
                            'accounts' => [
                                ['currency' => $account->currency ?? 'USD'],
                            ],
                        ];

                    }
                }
            }

            $keyword = trim((string) ($filter['keyword'] ?? ''));
            if ($keyword !== '') {
                $needle = mb_strtolower($keyword);
                $accountsList = array_values(array_filter(
                    $accountsList,
                    fn ($item) =>
                        str_contains($this->normalizeSearchValue($item['account_name'] ?? null), $needle)
                        || str_contains($this->normalizeSearchValue($item['account_id'] ?? null), $needle)
                        || str_contains($this->normalizeSearchValue($item['owner_name'] ?? null), $needle)
                        || str_contains($this->normalizeSearchValue($item['bm_ids'] ?? null), $needle)
                ));
            }

            // Tính lại thống kê sau khi filter
            $stats = [
                'total_accounts' => 0,
                'active_accounts' => 0,
                'disabled_accounts' => 0,
                'by_platform' => [
                    PlatformType::META->value => [
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                    ],
                    PlatformType::GOOGLE->value => [
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                    ],
                ],
            ];

            foreach ($accountsList as $accountItem) {
                $platform = $accountItem['platform'] ?? null;
                $stats['total_accounts'] += $accountItem['total_accounts'] ?? 0;
                $stats['active_accounts'] += $accountItem['active_accounts'] ?? 0;
                $stats['disabled_accounts'] += $accountItem['disabled_accounts'] ?? 0;

                if ($platform !== null && isset($stats['by_platform'][$platform])) {
                    $stats['by_platform'][$platform]['total_accounts'] += $accountItem['total_accounts'] ?? 0;
                    $stats['by_platform'][$platform]['active_accounts'] += $accountItem['active_accounts'] ?? 0;
                    $stats['by_platform'][$platform]['disabled_accounts'] += $accountItem['disabled_accounts'] ?? 0;
                }
            }

            $bmArray = array_values($accountsList);

            $perPage = $queryListDTO->perPage ?? 10;
            $page = $queryListDTO->page ?? 1;
            $total = count($bmArray);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($bmArray, $offset, $perPage);

            $paginator = new LengthAwarePaginator(
                items: $paginatedData,
                total: $total,
                perPage: $perPage,
                currentPage: $page,
                options: ['path' => request()->url(), 'query' => request()->query()]
            );

            return ServiceReturn::success(data: [
                'paginator' => $paginator,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'BusinessManagerService@getListBusinessManagers error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xác định account có đang active hay không
     */
    private function isAccountActive(int $platform, ?int $status): bool
    {
        if ($status === null) {
            return false;
        }

        if ($platform === PlatformType::META->value) {
            $enum = MetaAdsAccountStatus::fromValue($status);
            return $enum === MetaAdsAccountStatus::ACTIVE || $enum === MetaAdsAccountStatus::ANY_ACTIVE;
        }

        if ($platform === PlatformType::GOOGLE->value) {
            $enum = GoogleCustomerStatus::fromValue($status);
            return $enum === GoogleCustomerStatus::ENABLED;
        }

        return false;
    }

    /**
     * Chuẩn hóa giá trị dùng cho tìm kiếm keyword
     */
    private function normalizeSearchValue(string|array|null $value): string
    {
        if (is_array($value)) {
            return mb_strtolower(implode(', ', array_map(
                fn ($v) => trim((string) $v),
                $value,
            )));
        }

        return mb_strtolower(trim((string) $value));
    }

    /**
     * Lấy danh sách accounts của một BM/MCC
     */
    public function getAccountsByBmId(string $bmId, ?int $platform = null): ServiceReturn
    {
        try {
            // Lấy user hiện tại để check role
            $user = Auth::user();

            // Tìm service_users có BM ID này
            $query = $this->serviceUserRepository->query()
                ->with(['user:id,name,username', 'package:id,platform'])
                ->where('status', ServiceUserStatus::ACTIVE->value);

            // Nếu là customer, chỉ lấy service_users của chính họ
            if ($user && $user->role === UserRole::CUSTOMER->value) {
                $query->where('user_id', $user->id);
            } elseif ($user && $user->role === UserRole::AGENCY->value) {
                // Agency: lấy service_users của chính họ + service_users của các customer mà họ quản lý
                $managedCustomerIds = $this->userReferralRepository->query()
                    ->where('referrer_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('referred_id')
                    ->toArray();

                $userIds = array_merge([$user->id], $managedCustomerIds);
                $query->whereIn('user_id', $userIds);
            }

            $serviceUsers = $query->get()->filter(function ($serviceUser) use ($bmId) {
                $config = $serviceUser->config_account ?? [];

                $configBmIds = [];
                if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                    foreach ($config['accounts'] as $account) {
                        if (isset($account['bm_ids']) && is_array($account['bm_ids'])) {
                            $configBmIds = array_merge($configBmIds, array_filter($account['bm_ids'], fn($id) => !empty(trim($id ?? ''))));
                        }
                    }
                } else {
                    $configBmId = $config['bm_id'] ?? null;
                    if ($configBmId) {
                        $configBmIds[] = $configBmId;
                    }
                }

                if (empty($configBmIds)) {
                    $configBmIds = ['user_' . $serviceUser->user_id];
                }

                return in_array($bmId, $configBmIds);
            });

            $accounts = [];

            foreach ($serviceUsers as $serviceUser) {
                $servicePlatform = $serviceUser->package->platform ?? null;

                if ($platform && $servicePlatform != $platform) {
                    continue;
                }

                if ($servicePlatform === PlatformType::META->value) {
                    $metaAccounts = $this->metaAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->withCount('metaAdsCampaigns')
                        ->get();

                    foreach ($metaAccounts as $account) {
                        $accounts[] = [
                            'id' => (string) $account->id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'service_user_id' => (string) $serviceUser->id,
                            'spend_cap' => $account->spend_cap,
                            'amount_spent' => $account->amount_spent,
                            'balance' => $account->balance,
                            'currency' => $account->currency,
                            'account_status' => $account->account_status,
                            'total_campaigns' => $account->meta_ads_campaigns_count ?? 0,
                        ];
                    }
                } elseif ($servicePlatform === PlatformType::GOOGLE->value) {
                    $googleAccounts = $this->googleAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->get();

                    foreach ($googleAccounts as $account) {
                        $accounts[] = [
                            'id' => (string) $account->id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'service_user_id' => (string) $serviceUser->id,
                            'spend_cap' => null, // Google không có spend_cap
                            'amount_spent' => '0',
                            'balance' => $account->balance ?? '0',
                            'currency' => $account->currency ?? 'USD',
                            'account_status' => $account->account_status ?? 1,
                            'total_campaigns' => 0, // TODO: Đếm từ campaigns
                        ];
                    }
                }
            }

            return ServiceReturn::success(data: $accounts);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'BusinessManagerService@getAccountsByBmId error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy accounts từ platform config
     */
    private function getAccountsFromPlatformConfig(?Carbon $dateStart, ?Carbon $dateEnd, ?int $platformFilter): array
    {
        $accountsList = [];

        // Lấy platform configs active
        $platforms = $platformFilter ? [$platformFilter] : [PlatformType::META->value, PlatformType::GOOGLE->value];

        foreach ($platforms as $platform) {
            $platformSetting = $this->platformSettingRepository->findActiveByPlatform($platform);
            if (!$platformSetting || !$platformSetting->config) {
                continue;
            }

            $config = $platformSetting->config;

            if ($platform === PlatformType::META->value) {
                $bmId = $config['business_manager_id'] ?? null;
                if (!$bmId) {
                    continue;
                }

                // Lấy tất cả accounts từ Meta mà có account_id thuộc BM gốc
                // Tìm accounts có service_user_id = null (chưa được gán cho user nào)
                // Hoặc có thể lấy tất cả accounts và đánh dấu những account chưa có service_user_id
                $metaAccounts = $this->metaAccountRepository->query()
                    ->whereNull('service_user_id')
                    ->get();

                foreach ($metaAccounts as $account) {
                    // Tính spend cho từng account
                    if ($dateStart && $dateEnd) {
                        $spend = $this->metaAdsAccountInsightRepository->query()
                            ->where('meta_account_id', $account->id)
                            ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                            ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                            ->first();
                        $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                    } else {
                        $spendValue = (string) ($account->amount_spent ?? '0');
                    }

                    $balanceValue = (string) ($account->balance ?? '0');
                    $status = $account->account_status !== null ? (int) $account->account_status : null;
                    $isActive = $this->isAccountActive((int) $platform, $status);

                    $accountsList[] = [
                        'id' => (string) $account->account_id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => null,
                        'bm_ids' => [$bmId],
                        'name' => $account->account_name,
                        'platform' => $platform,
                        'owner_name' => $platformSetting->name,
                        'owner_id' => null,
                        'total_accounts' => 1,
                        'active_accounts' => $isActive ? 1 : 0,
                        'disabled_accounts' => $isActive ? 0 : 1,
                        'total_spend' => $spendValue,
                        'total_balance' => $balanceValue,
                        'currency' => $account->currency ?? 'USD',
                        'accounts' => [
                            ['currency' => $account->currency ?? 'USD'],
                        ],
                    ];
                }
            } elseif ($platform === PlatformType::GOOGLE->value) {
                $mccId = $config['login_customer_id'] ?? null;
                if (!$mccId) {
                    continue;
                }

                // Lấy tất cả accounts từ Google mà có account_id thuộc MCC gốc
                // Tìm accounts có service_user_id = null (chưa được gán cho user nào)
                $googleAccounts = $this->googleAccountRepository->query()
                    ->whereNull('service_user_id')
                    ->get();

                foreach ($googleAccounts as $account) {
                    // Tính spend từ insights nếu có date range
                    if ($dateStart && $dateEnd) {
                        $spend = $this->googleAdsAccountInsightRepository->query()
                            ->where('google_account_id', $account->id)
                            ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                            ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                            ->first();
                        $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                    } else {
                        $spendValue = '0';
                    }

                    $balanceValue = (string) ($account->balance ?? '0');
                    $status = $account->account_status !== null ? (int) $account->account_status : null;
                    $isActive = $this->isAccountActive((int) $platform, $status);

                    $accountsList[] = [
                        'id' => (string) $account->account_id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => null, // Chưa được gán cho user nào
                        'bm_ids' => [$mccId],
                        'name' => $account->account_name,
                        'platform' => $platform,
                        'owner_name' => 'System (Chưa gán)',
                        'owner_id' => null,
                        'total_accounts' => 1,
                        'active_accounts' => $isActive ? 1 : 0,
                        'disabled_accounts' => $isActive ? 0 : 1,
                        'total_spend' => $spendValue,
                        'total_balance' => $balanceValue,
                        'currency' => $account->currency ?? 'USD',
                        'accounts' => [
                            ['currency' => $account->currency ?? 'USD'],
                        ],
                    ];
                }
            }
        }

        return $accountsList;
    }
}

