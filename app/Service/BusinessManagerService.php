<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\ServiceUserRepository;
use App\Core\Logging;
use Illuminate\Pagination\LengthAwarePaginator;

class BusinessManagerService
{
    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected GoogleAccountRepository $googleAccountRepository,
    ) {
    }

    /**
     * Lấy danh sách Business Managers / MCC với pagination
     */
    public function getListBusinessManagers(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            
            // Lấy tất cả service_users active
            $query = $this->serviceUserRepository->query()
                ->with(['user:id,name,username', 'package:id,platform'])
                ->where('status', ServiceUserStatus::ACTIVE->value);
            
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
                
                // Lấy BM ID từ config (có thể là bm_id hoặc từ ad_account_ids)
                $bmId = $config['bm_id'] ?? null;
                if (!$bmId) {
                    // Nếu không có BM ID, dùng user_id làm key
                    $bmId = 'user_' . $serviceUser->user_id;
                }
                
                $key = $platform . '_' . $bmId;
                
                if (!isset($bmList[$key])) {
                    $bmList[$key] = [
                        'id' => $bmId,
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
                        $bmList[$key]['total_spend'] = bcadd($bmList[$key]['total_spend'], $account->amount_spent ?? '0', 2);
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
            // Tìm service_users có BM ID này
            $query = $this->serviceUserRepository->query()
                ->with(['user:id,name,username', 'package:id,platform'])
                ->where('status', ServiceUserStatus::ACTIVE->value);
            
            $serviceUsers = $query->get()->filter(function ($serviceUser) use ($bmId) {
                $config = $serviceUser->config_account ?? [];
                $configBmId = $config['bm_id'] ?? null;
                if (!$configBmId) {
                    $configBmId = 'user_' . $serviceUser->user_id;
                }
                return $configBmId === $bmId;
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
                            'spend_cap' => null, // Google không có spend_cap
                            'amount_spent' => '0', // TODO: Tính từ campaigns
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

