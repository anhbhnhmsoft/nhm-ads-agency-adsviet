<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\GoogleAdsAccountInsightRepository;
use App\Core\Logging;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BusinessManagerService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected GoogleAccountRepository $googleAccountRepository,
        protected MetaAdsAccountInsightRepository $metaAdsAccountInsightRepository,
        protected GoogleAdsAccountInsightRepository $googleAdsAccountInsightRepository,
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

            // Nếu là customer hoặc agency, chỉ lấy service_users của chính họ
            if ($user && in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                $query->where('user_id', $user->id);
            }

            $query = $this->serviceUserRepository->sortQuery($query, 'created_at', 'desc');
            
            // Filter theo platform nếu có
            if (!empty($filter['platform'])) {
                $query->whereHas('package', function ($q) use ($filter) {
                    $q->where('platform', (int) $filter['platform']);
                });
            }
            
            $serviceUsers = $query->get();
            
            // Group theo BM ID hoặc user để tạo danh sách BM/MCC
            $bmList = [];
            
            foreach ($serviceUsers as $serviceUser) {
                $config = $serviceUser->config_account ?? [];
                $platform = $serviceUser->package->platform ?? null;
                
                // Bỏ qua nếu không có platform
                if (!$platform) {
                    continue;
                }
                
                $bmIds = [];
                if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                    foreach ($config['accounts'] as $account) {
                        if (isset($account['bm_ids']) && is_array($account['bm_ids'])) {
                            $bmIds = array_merge($bmIds, array_filter($account['bm_ids'], fn($id) => !empty(trim($id ?? ''))));
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
                
                $bmIdForKey = $bmIds[0];
                $key = $platform . '_' . $bmIdForKey;
                
                if (!isset($bmList[$key])) {
                    $bmIdDisplay = count($bmIds) > 1 ? implode(', ', $bmIds) : $bmIds[0];
                    
                    $bmList[$key] = [
                        'id' => $bmIdDisplay,
                        'bm_ids' => $bmIds,
                        'name' => $config['display_name'] ?? $serviceUser->user->name ?? $serviceUser->user->username ?? 'Unknown',
                        'platform' => $platform,
                        'owner_name' => $serviceUser->user->name ?? $serviceUser->user->username ?? 'Unknown',
                        'owner_id' => $serviceUser->user_id,
                        'total_accounts' => 0,
                        'active_accounts' => 0,
                        'disabled_accounts' => 0,
                        'total_spend' => '0',
                        'total_balance' => '0',
                        'currency' => 'USD',
                    ];
                }
                
                // Tính tổng accounts, spend, balance
                if ($platform === PlatformType::META->value) {
                    $accounts = $this->metaAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->get();
                    
                    foreach ($accounts as $account) {
                        $bmList[$key]['total_accounts']++;
                        if ($account->account_status == 1) { // Active
                            $bmList[$key]['active_accounts']++;
                        } else {
                            $bmList[$key]['disabled_accounts']++;
                        }
                        
                        // Tính spend từ insights nếu có date range, nếu không dùng amount_spent
                        if ($dateStart && $dateEnd) {
                            $spend = $this->metaAdsAccountInsightRepository->query()
                                ->where('meta_account_id', $account->id)
                                ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                                ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                                ->first();
                            $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                            $bmList[$key]['total_spend'] = bcadd($bmList[$key]['total_spend'], $spendValue, 2);
                        } else {
                            $bmList[$key]['total_spend'] = bcadd($bmList[$key]['total_spend'], $account->amount_spent ?? '0', 2);
                        }
                        
                        $bmList[$key]['total_balance'] = bcadd($bmList[$key]['total_balance'], $account->balance ?? '0', 2);
                        $bmList[$key]['currency'] = $account->currency ?? 'USD';
                    }
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $accounts = $this->googleAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->get();
                    
                    foreach ($accounts as $account) {
                        $bmList[$key]['total_accounts']++;
                        if ($account->account_status == 1) { // Active
                            $bmList[$key]['active_accounts']++;
                        } else {
                            $bmList[$key]['disabled_accounts']++;
                        }
                        
                        // Tính spend từ insights nếu có date range
                        if ($dateStart && $dateEnd) {
                            $spend = $this->googleAdsAccountInsightRepository->query()
                                ->where('google_account_id', $account->id)
                                ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')])
                                ->selectRaw('COALESCE(SUM(CAST(spend AS DECIMAL(15,2))), 0) as total_spend')
                                ->first();
                            $spendValue = $spend && isset($spend->total_spend) ? (string) $spend->total_spend : '0';
                            $bmList[$key]['total_spend'] = bcadd($bmList[$key]['total_spend'], $spendValue, 2);
                        } else {
                            // Google không có amount_spent trong account, để 0 hoặc tính từ insights tổng
                            $bmList[$key]['total_spend'] = bcadd($bmList[$key]['total_spend'], '0', 2);
                        }
                        
                        $bmList[$key]['total_balance'] = bcadd($bmList[$key]['total_balance'], $account->balance ?? '0', 2);
                        $bmList[$key]['currency'] = $account->currency ?? 'USD';
                    }
                }
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
            
            // Chuyển thành array và paginate
            $bmArray = array_values($bmList);

            // Đảm bảo total_spend và total_balance luôn có giá trị mặc định
            foreach ($bmArray as &$bm) {
                $bm['total_spend'] = $bm['total_spend'] ?? '0';
                $bm['total_balance'] = $bm['total_balance'] ?? '0';
                if (empty($bm['total_spend']) || $bm['total_spend'] === null) {
                    $bm['total_spend'] = '0';
                }
                if (empty($bm['total_balance']) || $bm['total_balance'] === null) {
                    $bm['total_balance'] = '0';
                }
            }
            unset($bm);

            foreach ($bmArray as $bm) {
                $stats['total_accounts'] += $bm['total_accounts'];
                $stats['active_accounts'] += $bm['active_accounts'];
                $stats['disabled_accounts'] += $bm['disabled_accounts'];

                if (isset($stats['by_platform'][$bm['platform']])) {
                    $stats['by_platform'][$bm['platform']]['total_accounts'] += $bm['total_accounts'];
                    $stats['by_platform'][$bm['platform']]['active_accounts'] += $bm['active_accounts'];
                    $stats['by_platform'][$bm['platform']]['disabled_accounts'] += $bm['disabled_accounts'];
                }
            }

            // Nếu là customer hoặc agency, chỉ hiển thị 1 BM/MCC đầu tiên
            if ($user && in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                $bmArray = array_slice($bmArray, 0, 1);
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
            
            // Nếu là customer hoặc agency, chỉ lấy service_users của chính họ
            if ($user && in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                $query->where('user_id', $user->id);
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
}

