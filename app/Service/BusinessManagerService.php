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
use App\Repositories\MetaBusinessManagerRepository;
use App\Repositories\GoogleMccManagerRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\GoogleAdsCampaignRepository;
use Carbon\Carbon;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Repositories\PlatformSettingRepository;
use App\Service\PlatformSettingService;

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
        protected PlatformSettingService $platformSettingService,
        protected MetaBusinessManagerRepository $metaBusinessManagerRepository,
        protected GoogleMccManagerRepository $googleMccManagerRepository,
        protected MetaAdsCampaignRepository $metaAdsCampaignRepository,
        protected GoogleAdsCampaignRepository $googleAdsCampaignRepository,
    ) {
    }

    /**
     * Lấy danh sách BM/MCC con
     */
    public function getChildManagersForFilter(): array
    {
        $activeMetaSettingResult = $this->platformSettingService->findPlatformActive(PlatformType::META->value);
        $activeMetaSetting = $activeMetaSettingResult->isSuccess() ? $activeMetaSettingResult->getData() : null;
        $activeMetaBmId = $activeMetaSetting
            ? $this->platformSettingService->getMetaScopedBusinessManagerId($activeMetaSetting->config ?? [])
            : null;

        $metaChildren = [];
        if ($activeMetaSetting) {
            $metaChildQuery = $this->metaBusinessManagerRepository->query();
            if ($activeMetaBmId) {
                $metaChildQuery
                    ->whereNotNull('parent_bm_id')
                    ->where('parent_bm_id', $activeMetaBmId);
            }

            $metaChildren = $metaChildQuery
                ->get(['bm_id', 'name', 'parent_bm_id'])
                ->map(fn ($bm) => [
                    'id' => (string) $bm->bm_id,
                    'name' => $bm->name ?? (string) $bm->bm_id,
                    'parent_id' => $bm->parent_bm_id ? (string) $bm->parent_bm_id : '',
                ])
                ->toArray();
        }

        $activeGoogleSettingResult = $this->platformSettingService->findPlatformActive(PlatformType::GOOGLE->value);
        $activeGoogleSetting = $activeGoogleSettingResult->isSuccess() ? $activeGoogleSettingResult->getData() : null;
        $activeGoogleManagerId = $activeGoogleSetting ? ($activeGoogleSetting->config['login_customer_id'] ?? null) : null;

        $googleChildren = [];
        if ($activeGoogleManagerId) {
            $googleChildren = $this->googleMccManagerRepository->query()
                ->whereNotNull('parent_mcc_id')
                ->where('parent_mcc_id', $activeGoogleManagerId)
                ->get(['mcc_id', 'name', 'parent_mcc_id'])
                ->map(fn ($mcc) => [
                    'id' => (string) $mcc->mcc_id,
                    'name' => $mcc->name ?? (string) $mcc->mcc_id,
                    'parent_id' => (string) $mcc->parent_mcc_id,
                ])
                ->toArray();
        }

        return [
            'meta' => $metaChildren,
            'google' => $googleChildren,
        ];
    }

    /**
     * Lấy danh sách Business Managers / MCC với pagination
     */
    public function getListBusinessManagers(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            $viewMode = $filter['view'] ?? 'bm';

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

            // --- Lọc theo BM/MCC đang chọn nếu là Admin/Manager ---
            if ($user && in_array($user->role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                $metaSettingId = session('active_meta_setting_id');
                $googleSettingId = session('active_google_setting_id');
                
                if ($metaSettingId || $googleSettingId) {
                    $query->where(function ($subQ) use ($metaSettingId, $googleSettingId) {
                        $hasFilter = false;

                        if ($metaSettingId) {
                            $metaSetting = $this->platformSettingService->find($metaSettingId)->getData();
                            $metaBmId = $metaSetting
                                ? $this->platformSettingService->getMetaScopedBusinessManagerId($metaSetting->config ?? [])
                                : null;

                            if ($metaSetting && !$metaBmId) {
                                $subQ->orWhere(function ($metaQ) {
                                    $metaQ->whereHas('package', fn($p) => $p->where('platform', PlatformType::META->value));
                                });
                                $hasFilter = true;
                            } elseif ($metaSetting && $metaBmId) {
                                $subQ->orWhere(function ($metaQ) use ($metaBmId) {
                                    $metaQ->whereHas('package', fn($p) => $p->where('platform', PlatformType::META->value))
                                          ->where(function ($jsonQ) use ($metaBmId) {
                                              $jsonQ->whereJsonContains('config_account->business_manager_id', $metaBmId)
                                                    ->orWhereJsonContains('config_account->bm_id', $metaBmId)
                                                    ->orWhereJsonContains('config_account->child_bm_id', $metaBmId);
                                          });
                                });
                                $hasFilter = true;
                            }
                        }
                        
                        if ($googleSettingId) {
                            $googleSetting = $this->platformSettingService->find($googleSettingId)->getData();
                            if ($googleSetting && isset($googleSetting->config['login_customer_id'])) {
                                $mccId = (string) $googleSetting->config['login_customer_id'];
                                $subQ->orWhere(function ($googleQ) use ($mccId) {
                                    $googleQ->whereHas('package', fn($p) => $p->where('platform', PlatformType::GOOGLE->value))
                                            ->where(function ($jsonQ) use ($mccId) {
                                                $jsonQ->whereJsonContains('config_account->login_customer_id', $mccId)
                                                      ->orWhereJsonContains('config_account->customer_manager_id', $mccId);
                                            });
                                });
                                $hasFilter = true;
                            }
                        }

                        if (!$hasFilter) {
                            $subQ->whereNull('id');
                        }
                    });
                }
            }

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

            // Filter theo BM/MCC con nếu có
            $childManagerId = isset($filter['child_manager_id']) && $filter['child_manager_id'] !== ''
                ? (string) $filter['child_manager_id']
                : null;

            $bmNameMap = $this->metaBusinessManagerRepository->query()
                ->pluck('name', 'bm_id')
                ->toArray();

            $bmParentMap = $this->metaBusinessManagerRepository->query()
                ->pluck('parent_bm_id', 'bm_id')
                ->toArray();

            $mccNameMap = $this->googleMccManagerRepository->query()
                ->pluck('name', 'mcc_id')
                ->toArray();

            $mccParentMap = $this->googleMccManagerRepository->query()
                ->pluck('parent_mcc_id', 'mcc_id')
                ->toArray();

            $accountToGroupNamesMap = DB::table('meta_account_asset_group')
                ->join('meta_business_asset_groups', 'meta_account_asset_group.meta_business_asset_group_id', '=', 'meta_business_asset_groups.id')
                ->select('meta_account_asset_group.meta_account_id', 'meta_business_asset_groups.name')
                ->get()
                ->groupBy('meta_account_id')
                ->map(fn($items) => $items->pluck('name')->toArray())
                ->toArray();

            // Danh sách tài khoản quảng cáo
            $accountsList = [];

            // Lấy accounts từ platform config
            if ($user && $user->role === UserRole::ADMIN->value) {
                $platformConfigAccounts = $this->getAccountsFromPlatformConfig(
                    $dateStart,
                    $dateEnd,
                    $filter['platform'] ?? null,
                    $bmNameMap,
                    $mccNameMap,
                    $bmParentMap,
                    $mccParentMap,
                    $accountToGroupNamesMap
                );
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
                // Ưu tiên hiển thị BM con nếu có, nếu không thì hiển thị BM gốc
                $bmIds = [];
                $childBmId = $config['child_bm_id'] ?? null;
                
                if ($childBmId) {
                    // Nếu có BM con, hiển thị BM con
                    $bmIds[] = $childBmId;
                } else {
                    // Nếu không có BM con, lấy từ accounts hoặc bm_id
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

                        // Đếm số campaigns cho account này
                        $totalCampaigns = $this->metaAdsCampaignRepository->query()
                            ->where('meta_account_id', $account->id)
                            ->count();
                        // Active campaigns: status = 'ACTIVE' hoặc effective_status = 'ACTIVE'
                        $activeCampaigns = $this->metaAdsCampaignRepository->query()
                            ->where('meta_account_id', $account->id)
                            ->where(function ($q) {
                                $q->where('status', 'ACTIVE')
                                  ->orWhere('effective_status', 'ACTIVE');
                            })
                            ->count();
                        $disabledCampaigns = $totalCampaigns - $activeCampaigns;

                        $accountBmId = $account->business_manager_id ?? null;
                        $bmIdsForRow = $accountBmId ? [$accountBmId] : $bmIds;
                        $parentBmIdForRow = $accountBmId
                            ? ($bmParentMap[$accountBmId] ?? null)
                            : (isset($bmIdsForRow[0]) ? ($bmParentMap[$bmIdsForRow[0]] ?? null) : null);

                        $bmDisplayName = $accountBmId && isset($bmNameMap[$accountBmId])
                            ? $bmNameMap[$accountBmId]
                            : $this->resolveBmName($bmIdsForRow, $bmNameMap);
                        
                        // Ưu tiên hiển thị tên Nhóm tài sản (Tên khách hàng) nếu có (Có thể có nhiều nhóm)
                        $assetGroupNames = $accountToGroupNamesMap[$account->id] ?? [];
                        
                        if (!empty($assetGroupNames)) {
                            // Nếu thuộc nhiều nhóm, nối tên bằng dấu phẩy (Pete Willam, Test...)
                            $displayBmName = implode(', ', $assetGroupNames);
                        } else {
                            // Nếu không có nhóm nào, dùng tên BM hoặc tên hiển thị cấu hình
                            $displayBmName = !empty($config['display_name']) ? $config['display_name'] : $bmDisplayName;
                        }

                        $accountsList[] = $this->buildBusinessManagerListItem($account, $serviceUser, $platform, $ownerName, $bmIdsForRow, $parentBmIdForRow, $displayBmName, $spendValue, $balanceValue, $totalCampaigns, $activeCampaigns, $disabledCampaigns, $childBmId);
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

                        // Đếm số campaigns cho account này
                        $totalCampaigns = $this->googleAdsCampaignRepository->query()
                            ->where('google_account_id', $account->id)
                            ->count();
                        // Active campaigns: status = 'ENABLED' hoặc effective_status = 'ENABLED'
                        $activeCampaigns = $this->googleAdsCampaignRepository->query()
                            ->where('google_account_id', $account->id)
                            ->where(function ($q) {
                                $q->where('status', 'ENABLED')
                                  ->orWhere('effective_status', 'ENABLED');
                            })
                            ->count();
                        $disabledCampaigns = $totalCampaigns - $activeCampaigns;

                        $mccDisplayName = null;
                        $customerManagerId = $account->customer_manager_id ?? null;
                        $bmIdsForRow = $customerManagerId ? [$customerManagerId] : $bmIds;

                        if ($customerManagerId && isset($mccNameMap[$customerManagerId])) {
                            $mccDisplayName = $mccNameMap[$customerManagerId];
                        } elseif (!empty($bmIdsForRow)) {
                            $mccDisplayName = $this->resolveMccName($bmIdsForRow, $mccNameMap);
                        }

                        $parentMccId = $customerManagerId
                            ? ($mccParentMap[$customerManagerId] ?? null)
                            : (isset($bmIdsForRow[0]) ? ($mccParentMap[$bmIdsForRow[0]] ?? null) : null);

                        $displayMccName = !empty($config['display_name']) ? $config['display_name'] : $mccDisplayName;

                        $accountsList[] = [
                            'id' => (string) $account->id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'service_user_id' => (string) $serviceUser->id,
                            'bm_ids' => $bmIdsForRow,
                            'bm_name' => $displayMccName,
                            'parent_bm_id' => $parentMccId,
                            'name' => $account->account_name ?? $config['display_name'] ?? $ownerName,
                            'platform' => $platform,
                            'owner_name' => $ownerName,
                            'owner_id' => $serviceUser->user_id,
                            'total_campaigns' => $totalCampaigns,
                            'active_campaigns' => $activeCampaigns,
                            'disabled_campaigns' => $disabledCampaigns,
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

            // Filter theo BM/MCC con sau khi đã build danh sách account
            if ($viewMode === 'bm' && (empty($filter['platform']) || (int) $filter['platform'] === PlatformType::META->value)) {
                $existingMetaBmIds = [];
                foreach ($accountsList as $item) {
                    if (($item['platform'] ?? null) !== PlatformType::META->value) {
                        continue;
                    }

                    foreach (($item['bm_ids'] ?? []) as $itemBmId) {
                        $existingMetaBmIds[] = (string) $itemBmId;
                    }
                }
                $existingMetaBmIds = array_values(array_unique($existingMetaBmIds));

                $activeMetaSettingResult = $this->platformSettingService->findPlatformActive(PlatformType::META->value);
                $activeMetaSetting = $activeMetaSettingResult->isSuccess() ? $activeMetaSettingResult->getData() : null;
                $activeMetaBmId = $activeMetaSetting
                    ? $this->platformSettingService->getMetaScopedBusinessManagerId($activeMetaSetting->config ?? [])
                    : null;

                $emptyBmQuery = $this->metaBusinessManagerRepository->query();
                if ($activeMetaBmId) {
                    $emptyBmQuery->where(function ($q) use ($activeMetaBmId) {
                        $q->where('bm_id', (string) $activeMetaBmId)
                            ->orWhere('parent_bm_id', (string) $activeMetaBmId);
                    });
                }

                if (!empty($existingMetaBmIds)) {
                    $emptyBmQuery->whereNotIn('bm_id', $existingMetaBmIds);
                }

                $emptyBmQuery->get(['bm_id', 'parent_bm_id', 'name', 'currency'])->each(function ($bm) use (&$accountsList) {
                    $accountsList[] = [
                        'id' => 'meta-bm-' . (string) $bm->bm_id,
                        'account_id' => null,
                        'account_name' => null,
                        'service_user_id' => null,
                        'bm_ids' => [(string) $bm->bm_id],
                        'bm_name' => $bm->name ?: (string) $bm->bm_id,
                        'parent_bm_id' => $bm->parent_bm_id ? (string) $bm->parent_bm_id : null,
                        'name' => $bm->name ?: (string) $bm->bm_id,
                        'platform' => PlatformType::META->value,
                        'owner_name' => null,
                        'owner_id' => null,
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                        'total_spend' => '0',
                        'total_balance' => '0',
                        'currency' => $bm->currency ?? 'USD',
                        'accounts' => [
                            ['currency' => $bm->currency ?? 'USD'],
                        ],
                    ];
                });
            }

            if ($childManagerId) {
                $accountsList = array_values(array_filter(
                    $accountsList,
                    fn ($item) =>
                        in_array($childManagerId, $item['bm_ids'] ?? [], true)
                ));
            }

            // Filter theo BM/MCC được chọn từ trang Quản lý BM/MCC
            $managerId = isset($filter['manager_id']) && $filter['manager_id'] !== ''
                ? trim((string) $filter['manager_id'])
                : null;
            if ($managerId) {
                $accountsList = array_values(array_filter(
                    $accountsList,
                    fn ($item) =>
                        in_array($managerId, array_map('strval', $item['bm_ids'] ?? []), true)
                        || (string) ($item['parent_bm_id'] ?? '') === $managerId
                ));
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
                        || str_contains($this->normalizeSearchValue($item['bm_name'] ?? null), $needle)
                        || str_contains($this->normalizeSearchValue($item['bm_ids'] ?? null), $needle)
                        || str_contains($this->normalizeSearchValue($item['parent_bm_id'] ?? null), $needle)
                ));
            }

            // Tính lại thống kê sau khi filter
            // Stats phải đếm số lượng accounts trong danh sách, không phải tổng campaigns
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
                if (empty($accountItem['account_id'])) {
                    continue;
                }

                $platform = $accountItem['platform'] ?? null;
                if ($platform === null) {
                    continue;
                }

                $status = $this->getAccountStatusByPlatform(
                    (int) $platform,
                    $accountItem['account_id'] ?? null
                );

                $isActive = $this->isAccountActive((int) $platform, $status);

                // Tổng theo tất cả platform
                $stats['total_accounts']++;
                $key = $isActive ? 'active_accounts' : 'disabled_accounts';
                $stats[$key]++;

                // Tổng theo từng platform
                if (isset($stats['by_platform'][$platform])) {
                    $stats['by_platform'][$platform]['total_accounts']++;
                    $stats['by_platform'][$platform][$key]++;
                }
            }

            if ($viewMode === 'bm') {
                $bmMap = [];
                foreach ($accountsList as $item) {
                    $bmIds = $item['bm_ids'] ?? [];
                    $primaryBmId = $bmIds[0] ?? $item['parent_bm_id'] ?? $item['account_id'] ?? $item['id'];
                    if (!$primaryBmId) {
                        continue;
                    }

                    $platform = $item['platform'] ?? null;
                    if ($platform === null) {
                        continue;
                    }

                    // Nếu có tên BM/Asset Group khác nhau thì tách dòng riêng (để hiện Pete Willam, Test...)
                    $key = $platform . '-' . $primaryBmId . '-' . ($item['bm_name'] ?? '');

                    $itemAccountCount = max(0, (int) ($item['total_accounts'] ?? 1));
                    $spendNum = isset($item['total_spend']) ? (float) $item['total_spend'] : 0.0;
                    $balanceNum = isset($item['total_balance']) ? (float) $item['total_balance'] : 0.0;

                    if (!isset($bmMap[$key])) {
                        $bmMap[$key] = [
                            'id' => $key,
                            'account_id' => null,
                            'account_name' => null,
                            'bm_ids' => [$primaryBmId],
                            'bm_name' => $item['bm_name'] ?? $item['name'] ?? $primaryBmId,
                            'name' => $item['bm_name'] ?? $item['name'] ?? $primaryBmId,
                            'platform' => $platform,
                            'service_user_id' => $item['service_user_id'] ?? null,
                            'owner_name' => $item['owner_name'] ?? null,
                            'owner_id' => $item['owner_id'] ?? null,
                            'total_accounts' => $itemAccountCount,
                            'total_spend' => (string) $spendNum,
                            'total_balance' => (string) $balanceNum,
                            'currency' => $item['currency'] ?? 'USD',
                            'parent_bm_id' => $item['parent_bm_id'] ?? null,
                            'accounts' => $item['accounts'] ?? [],
                            'child_bm_id' => $item['child_bm_id'] ?? null,
                        ];
                    } else {
                        $bmMap[$key]['total_accounts'] = ($bmMap[$key]['total_accounts'] ?? 0) + $itemAccountCount;
                        $currentSpend = isset($bmMap[$key]['total_spend']) ? (float) $bmMap[$key]['total_spend'] : 0.0;
                        $currentBalance = isset($bmMap[$key]['total_balance']) ? (float) $bmMap[$key]['total_balance'] : 0.0;
                        $bmMap[$key]['total_spend'] = (string) ($currentSpend + $spendNum);
                        $bmMap[$key]['total_balance'] = (string) ($currentBalance + $balanceNum);
                    }
                }

                $bmArray = array_values($bmMap);
            } else {
                $bmArray = array_values($accountsList);
            }

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
     * Lấy status của account theo từng platform
     */
    private function getAccountStatusByPlatform(int $platform, ?string $accountId): ?int
    {
        if (!$accountId) {
            return null;
        }

        return match ($platform) {
            PlatformType::META->value => $this->metaAccountRepository->query()
                ->where('account_id', $accountId)
                ->value('account_status'),
            PlatformType::GOOGLE->value => $this->googleAccountRepository->query()
                ->where('account_id', $accountId)
                ->value('account_status'),
            default => null,
        };
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
    /**
     * Lấy danh sách BM con của một BM gốc
     */
    public function getChildBusinessManagers(string $parentBmId): ServiceReturn
    {
        try {
            $childBMs = $this->metaBusinessManagerRepository->findByParentBmId($parentBmId);

            $data = $childBMs->map(function ($bm) {
                return [
                    'bm_id' => $bm->bm_id,
                    'name' => $bm->name ?? $bm->bm_id,
                    'parent_bm_id' => $bm->parent_bm_id,
                    'verification_status' => $bm->verification_status,
                    'currency' => $bm->currency,
                ];
            })->toArray();

            return ServiceReturn::success(data: $data);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'BusinessManagerService@getChildBusinessManagers error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

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

                // Kiểm tra child_bm_id trước (nếu user được gán cho BM con)
                $childBmId = $config['child_bm_id'] ?? null;
                if ($childBmId && $childBmId === $bmId) {
                    return true;
                }

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
                        ->where(function ($q) use ($bmId) {
                            $q->where('business_manager_id', $bmId)
                                ->orWhereNull('business_manager_id');
                        })
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
                            'platform' => PlatformType::META->value,
                        ];
                    }
                } elseif ($servicePlatform === PlatformType::GOOGLE->value) {
                    $googleAccounts = $this->googleAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->where(function ($q) use ($bmId) {
                            $q->where('customer_manager_id', $bmId)
                                ->orWhereNull('customer_manager_id');
                        })
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
                            'platform' => PlatformType::GOOGLE->value,
                            'total_campaigns' => 0, // TODO: Đếm từ campaigns
                        ];
                    }
                }
            }

            $existingAccountIds = array_map('strval', array_column($accounts, 'account_id'));

            if (!$platform || $platform === PlatformType::META->value) {
                $metaAccounts = $this->metaAccountRepository->query()
                    ->where('business_manager_id', $bmId)
                    ->withCount('metaAdsCampaigns')
                    ->get();

                foreach ($metaAccounts as $account) {
                    if (in_array((string) $account->account_id, $existingAccountIds, true)) {
                        continue;
                    }

                    $accounts[] = [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => $account->service_user_id ? (string) $account->service_user_id : null,
                        'spend_cap' => $account->spend_cap,
                        'amount_spent' => $account->amount_spent,
                        'balance' => $account->balance,
                        'currency' => $account->currency,
                        'account_status' => $account->account_status,
                        'total_campaigns' => $account->meta_ads_campaigns_count ?? 0,
                        'platform' => PlatformType::META->value,
                    ];

                    $existingAccountIds[] = (string) $account->account_id;
                }
            }

            if (!$platform || $platform === PlatformType::GOOGLE->value) {
                $googleAccounts = $this->googleAccountRepository->query()
                    ->where('customer_manager_id', $bmId)
                    ->get();

                foreach ($googleAccounts as $account) {
                    if (in_array((string) $account->account_id, $existingAccountIds, true)) {
                        continue;
                    }

                    $accounts[] = [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => $account->service_user_id ? (string) $account->service_user_id : null,
                        'spend_cap' => null,
                        'amount_spent' => '0',
                        'balance' => $account->balance ?? '0',
                        'currency' => $account->currency ?? 'USD',
                        'account_status' => $account->account_status ?? 1,
                        'total_campaigns' => 0,
                        'platform' => PlatformType::GOOGLE->value,
                    ];

                    $existingAccountIds[] = (string) $account->account_id;
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
    private function getAccountsFromPlatformConfig(?Carbon $dateStart, ?Carbon $dateEnd, ?int $platformFilter, array $bmNameMap, array $mccNameMap, ?array $bmParentMap = null, ?array $mccParentMap = null, array $accountToGroupNamesMap = []): array
    {
        $accountsList = [];

        // Lấy platform configs active - Chỉ lấy platform nào đang được lọc hoặc lấy cả 2 nếu không lọc gì
        $metaSettingId = session('active_meta_setting_id');
        $googleSettingId = session('active_google_setting_id');
        
        $platforms = [];
        if ($platformFilter) {
            $platforms = [$platformFilter];
        } else {
            if ($metaSettingId) $platforms[] = PlatformType::META->value;
            if ($googleSettingId) $platforms[] = PlatformType::GOOGLE->value;
            if (empty($platforms)) {
                $platforms = [PlatformType::META->value, PlatformType::GOOGLE->value];
            }
        }

        foreach ($platforms as $platform) {
            $activeSettingResult = $this->platformSettingService->findPlatformActive($platform);
            $platformSetting = $activeSettingResult->isSuccess() ? $activeSettingResult->getData() : null;
            
            if (!$platformSetting || !$platformSetting->config) {
                continue;
            }

            $config = $platformSetting->config;

            if ($platform === PlatformType::META->value) {
                $bmId = $this->platformSettingService->getMetaScopedBusinessManagerId($config);
                // Chỉ lấy tài khoản của BM gốc hoặc BM con (Để lọc bỏ các BM lạ từ Token Admin)
                $validBmQuery = $this->metaBusinessManagerRepository->query();
                if ($bmId) {
                    $validBmQuery->where(function ($q) use ($bmId) {
                        $q->where('bm_id', $bmId)
                            ->orWhere('parent_bm_id', $bmId);
                    });
                }

                $validBmIds = $validBmQuery->pluck('bm_id')->toArray();
                if (empty($validBmIds)) {
                    continue;
                }

                $metaAccounts = $this->metaAccountRepository->query()
                    ->whereIn('business_manager_id', $validBmIds)
                    ->whereNull('service_user_id')
                    ->get();

                foreach ($metaAccounts as $account) {
                    $accountBmId = $account->business_manager_id ?? $bmId;
                    $parentBmId = $accountBmId && $bmParentMap ? ($bmParentMap[$accountBmId] ?? null) : null;
                    $bmDisplayName = $accountBmId && isset($bmNameMap[$accountBmId])
                        ? $bmNameMap[$accountBmId]
                        : ($bmNameMap[$bmId] ?? null);

                    // Ưu tiên hiển thị tên Nhóm tài sản (Tên khách hàng) nếu có (Có thể có nhiều nhóm)
                    $assetGroupNames = $accountToGroupNamesMap[$account->id] ?? [];
                    if (!empty($assetGroupNames)) {
                        $displayBmName = implode(', ', $assetGroupNames);
                    } else {
                        $displayBmName = $bmDisplayName;
                    }

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
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => null,
                        'bm_ids' => [$accountBmId],
                        'bm_name' => $displayBmName,
                        'parent_bm_id' => $parentBmId,
                        'name' => $account->account_name,
                        'platform' => $platform,
                        'owner_name' => $platformSetting->name ?: 'System (Chưa gán)',
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
                    ->where('customer_manager_id', $mccId)
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

                    $mccDisplayName = $mccNameMap[$mccId] ?? null;
                    $parentMccId = $mccParentMap[$mccId] ?? null;

                    $accountsList[] = [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'service_user_id' => null, // Chưa được gán cho user nào
                        'bm_ids' => [$mccId],
                        'bm_name' => $mccDisplayName,
                        'parent_bm_id' => $parentMccId,
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

    /**
     * Helper tạo mảng data cho item trong danh sách BM/MCC
     */
    private function buildBusinessManagerListItem($account, $serviceUser, $platform, $ownerName, $bmIdsForRow, $parentBmIdForRow, $displayBmName, $spendValue, $balanceValue, $totalCampaigns, $activeCampaigns, $disabledCampaigns, $childBmId): array
    {
        return [
            'id' => (string) $account->id,
            'account_id' => $account->account_id,
            'account_name' => $account->account_name,
            'service_user_id' => (string) $serviceUser->id,
            'bm_ids' => $bmIdsForRow,
            'bm_name' => $displayBmName,
            'parent_bm_id' => $parentBmIdForRow,
            'name' => $account->account_name ?? $ownerName,
            'platform' => $platform,
            'owner_name' => $ownerName,
            'owner_id' => $serviceUser->user_id,
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'disabled_campaigns' => $disabledCampaigns,
            'total_spend' => $spendValue,
            'total_balance' => $balanceValue,
            'currency' => $account->currency ?? 'USD',
            'accounts' => [
                ['currency' => $account->currency ?? 'USD'],
            ],
            'child_bm_id' => $childBmId,
        ];
    }

    /**
     * Lấy tên BM theo bm_ids
     */
    private function resolveBmName(array $bmIds, array $bmNameMap): ?string
    {
        $firstBmId = $bmIds[0] ?? null;
        if (!$firstBmId) {
            return null;
        }

        return $bmNameMap[$firstBmId] ?? null;
    }

    /**
     * Lấy tên MCC theo mcc_ids
     */
    private function resolveMccName(array $mccIds, array $mccNameMap): ?string
    {
        $firstMccId = $mccIds[0] ?? null;
        if (!$firstMccId) {
            return null;
        }

        return $mccNameMap[$firstMccId] ?? null;
    }
}
