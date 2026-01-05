<?php

namespace App\Service;

use Carbon\Carbon;
use App\Core\Logging;
use App\Common\Helper;
use App\Core\QueryListDTO;
use App\Core\Cache\Caching;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Core\Cache\CacheKey;
use Google\ApiCore\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\WalletRepository;
use App\Common\Constants\User\UserRole;
use App\Repositories\ServiceUserRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\GoogleAccountRepository;
use App\Common\Constants\Platform\PlatformType;
use Illuminate\Pagination\LengthAwarePaginator;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Util\V22\ResourceNames;
use Google\Ads\GoogleAds\V22\Resources\Campaign;
use App\Repositories\GoogleAdsCampaignRepository;
use Google\Ads\GoogleAds\Lib\V22\GoogleAdsClient;
use Google\Ads\GoogleAds\V22\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\Lib\V22\GoogleAdsException;
use App\Common\Constants\Google\GoogleCustomerStatus;
use App\Common\Constants\Google\GoogleCampaignStatus;
use Google\Ads\GoogleAds\V22\Resources\CampaignBudget;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Repositories\GoogleAdsAccountInsightRepository;
use Google\Ads\GoogleAds\Lib\V22\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V22\Services\CampaignOperation;
use Google\Ads\GoogleAds\V22\Services\MutateCampaignsRequest;
use Google\Ads\GoogleAds\V22\Services\SearchGoogleAdsRequest;
use Google\Ads\GoogleAds\V22\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V22\Services\MutateCampaignBudgetsRequest;
use Google\Ads\GoogleAds\V22\Services\SearchGoogleAdsStreamRequest;
use Google\Ads\GoogleAds\V22\Services\Client\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V22\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleAdsService
{
    private ?array $platformConfig = null;

    /**
     * Clear platform config cache (useful when credentials are updated)
     */
    public function clearPlatformConfigCache(): void
    {
        $this->platformConfig = null;
    }

    public function __construct(
        protected ServiceUserRepository $serviceUserRepository,
        protected GoogleAccountRepository $googleAccountRepository,
        protected GoogleAdsAccountInsightRepository $googleAdsAccountInsightRepository,
        protected GoogleAdsCampaignRepository $googleAdsCampaignRepository,
        protected WalletRepository $walletRepository,
        protected PlatformSettingService $platformSettingService,
        protected GoogleAdsNotificationService $googleAdsNotificationService,
    ) {
    }

    public function getDashboardData(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $cached = Caching::getCache(CacheKey::CACHE_GOOGLE_DASHBOARD, (string) $user->id);
            if ($cached) {
                return ServiceReturn::success(data: $cached);
            }

            $serviceUsers = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->with('package')
                ->get()
                ->filter(fn ($serviceUser) => $serviceUser->package->platform === PlatformType::GOOGLE->value);

            $googleAccounts = $this->googleAccountRepository->query()
                ->whereIn('service_user_id', $serviceUsers->pluck('id'))
                ->get();

            $totalAccounts = $googleAccounts->count();
            $activeAccounts = $googleAccounts
                ->where('account_status', GoogleCustomerStatus::ENABLED->value)
                ->count();
            $pausedAccounts = $totalAccounts - $activeAccounts;

            $googleAccountIds = $googleAccounts->pluck('id')->toArray();

            $totalSpend = 0.0;
            $todaySpend = 0.0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalConversions = 0;
            $avgRoas = 0.0;
            $spendPercentChange = 0.0;
            $todaySpendPercentChange = 0.0;
            $impressionsPercentChange = 0.0;
            $clicksPercentChange = 0.0;
            $conversionsPercentChange = 0.0;

            if (!empty($googleAccountIds)) {
                $totalResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'maximum');
                if ($totalResult->isSuccess()) {
                    $totalData = $totalResult->getData();
                    $totalSpend = (float) ($totalData['spend'] ?? 0);
                    $totalImpressions = (int) ($totalData['impressions'] ?? 0);
                    $totalClicks = (int) ($totalData['clicks'] ?? 0);
                    $totalConversions = (int) ($totalData['conversions'] ?? 0);
                    $avgRoas = (float) ($totalData['roas'] ?? 0);
                }

                $todayResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'today');
                if ($todayResult->isSuccess()) {
                    $todayData = $todayResult->getData();
                    $todaySpend = (float) ($todayData['spend'] ?? 0);
                }

                $yesterdayResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'yesterday');
                $yesterdaySpend = 0.0;
                if ($yesterdayResult->isSuccess()) {
                    $yesterdayData = $yesterdayResult->getData();
                    $yesterdaySpend = (float) ($yesterdayData['spend'] ?? 0);
                }

                $last30dResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'last_30d');
                $last30dSpend = 0.0;
                $last30dImpressions = 0;
                $last30dClicks = 0;
                $last30dConversions = 0;
                if ($last30dResult->isSuccess()) {
                    $last30dData = $last30dResult->getData();
                    $last30dSpend = (float) ($last30dData['spend'] ?? 0);
                    $last30dImpressions = (int) ($last30dData['impressions'] ?? 0);
                    $last30dClicks = (int) ($last30dData['clicks'] ?? 0);
                    $last30dConversions = (int) ($last30dData['conversions'] ?? 0);
                }

                $last90dResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'last_90d');
                $previous30dSpend = 0.0;
                $previous30dImpressions = 0;
                $previous30dClicks = 0;
                $previous30dConversions = 0;
                if ($last90dResult->isSuccess()) {
                    $last90dData = $last90dResult->getData();
                    $previous30dSpend = (float) ($last90dData['spend'] ?? 0) - $last30dSpend;
                    $previous30dImpressions = (int) ($last90dData['impressions'] ?? 0) - $last30dImpressions;
                    $previous30dClicks = (int) ($last90dData['clicks'] ?? 0) - $last30dClicks;
                    $previous30dConversions = (int) ($last90dData['conversions'] ?? 0) - $last30dConversions;
                }

                $spendPercentChange = Helper::calculatePercentageChange($previous30dSpend, $last30dSpend);
                $impressionsPercentChange = Helper::calculatePercentageChange($previous30dImpressions, $last30dImpressions);
                $clicksPercentChange = Helper::calculatePercentageChange($previous30dClicks, $last30dClicks);
                $conversionsPercentChange = Helper::calculatePercentageChange($previous30dConversions, $last30dConversions);
                $todaySpendPercentChange = Helper::calculatePercentageChange($yesterdaySpend, $todaySpend);
            }

            $avgCpc = $totalClicks > 0 ? ($totalSpend / max($totalClicks, 1)) : 0.0;
            $avgCpm = $totalImpressions > 0 ? (($totalSpend / max($totalImpressions, 1)) * 1000) : 0.0;
            $conversionRate = $totalClicks > 0 ? (($totalConversions / $totalClicks) * 100) : 0.0;

            $hasPostpay = $serviceUsers->contains(function ($serviceUser) {
                $config = $serviceUser->config_account ?? [];
                return ($config['payment_type'] ?? '') === 'postpay';
            });

            $totalBudget = (float) ($serviceUsers->sum('budget') ?? 0);
            $budgetUsed = $todaySpend;
            if ($hasPostpay) {
                $totalBudget = 0.0;
                $budgetRemaining = 0.0;
                $budgetUsagePercent = 0.0;
            } else {
                $budgetRemaining = max(0, $totalBudget - $budgetUsed);
                $budgetUsagePercent = $totalBudget > 0 ? (($budgetUsed / $totalBudget) * 100) : 0.0;
            }

            $wallet = $this->walletRepository->findByUserId($user->id);
            $walletBalance = $wallet ? (float) $wallet->balance : 0.0;

            $data = [
                'wallet' => [
                    'balance' => number_format($walletBalance, 2, '.', ''),
                ],
                'overview' => [
                    'total_accounts' => $totalAccounts,
                    'active_accounts' => $activeAccounts,
                    'paused_accounts' => $pausedAccounts,
                    'total_spend' => number_format($totalSpend, 2, '.', ''),
                    'today_spend' => number_format($todaySpend, 2, '.', ''),
                    'total_services' => $serviceUsers->count(),
                    'available_services' => $serviceUsers->count(),
                    'critical_alerts' => 0,
                    'accounts_with_errors' => $totalAccounts - $activeAccounts,
                ],
                'metrics' => [
                    'total_spend' => [
                        'value' => number_format($totalSpend, 2, '.', ''),
                        'percent_change' => $spendPercentChange,
                    ],
                    'today_spend' => [
                        'value' => number_format($todaySpend, 2, '.', ''),
                        'percent_change' => $todaySpendPercentChange,
                    ],
                    'total_impressions' => [
                        'value' => $this->formatNumber($totalImpressions),
                        'percent_change' => $impressionsPercentChange,
                    ],
                    'total_clicks' => [
                        'value' => $this->formatNumber($totalClicks),
                        'percent_change' => $clicksPercentChange,
                    ],
                    'total_conversions' => [
                        'value' => $totalConversions,
                        'percent_change' => $conversionsPercentChange,
                    ],
                    'active_accounts' => [
                        'active' => $activeAccounts,
                        'total' => $totalAccounts,
                    ],
                ],
                'performance' => [
                    'conversion_rate' => number_format($conversionRate, 2, '.', ''),
                    'avg_cpc' => number_format($avgCpc, 2, '.', ''),
                    'avg_roas' => number_format($avgRoas, 1, '.', ''),
                    'avg_cpm' => number_format($avgCpm, 2, '.', ''),
                ],
                'budget' => [
                    'total' => number_format($totalBudget, 2, '.', ''),
                    'used' => number_format($budgetUsed, 2, '.', ''),
                    'remaining' => number_format($budgetRemaining, 2, '.', ''),
                    'usage_percent' => number_format($budgetUsagePercent, 2, '.', ''),
                    'is_postpay' => $hasPostpay,
                ],
                'alerts' => [
                    'critical_errors' => 0,
                    'accounts_with_errors' => $totalAccounts - $activeAccounts,
                ],
            ];

            Caching::setCache(
                key: CacheKey::CACHE_GOOGLE_DASHBOARD,
                value: $data,
                uniqueKey: (string) $user->id,
                expire: 5
            );

            return ServiceReturn::success(data: $data);
        } catch (\Throwable $exception) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    protected function getAccountsInsightsSummaryFromDatabase(array $googleAccountIds, string $datePreset = 'maximum'): ServiceReturn
    {
        try {
            if (empty($googleAccountIds)) {
                return ServiceReturn::success(data: [
                    'spend' => 0.0,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'roas' => 0.0,
                ]);
            }

            $query = $this->googleAdsAccountInsightRepository->query()
                ->whereIn('google_account_id', $googleAccountIds);

            $dateRange = $this->convertDatePresetToRange($datePreset);
            if ($dateRange) {
                if ($dateRange['start']) {
                    $query->where('date', '>=', $dateRange['start']);
                }
                if ($dateRange['end']) {
                    $query->where('date', '<=', $dateRange['end']);
                }
            }

            $insights = $query->get();

            $totalSpend = 0.0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalConversions = 0;
            $totalRoas = 0.0;
            $roasCount = 0;

            foreach ($insights as $insight) {
                $totalSpend += (float) ($insight->spend ?? 0);
                $totalImpressions += (int) ($insight->impressions ?? 0);
                $totalClicks += (int) ($insight->clicks ?? 0);
                $totalConversions += (int) ($insight->conversions ?? 0);

                if ($insight->roas) {
                    $totalRoas += (float) $insight->roas;
                    $roasCount++;
                }
            }

            $avgRoas = $roasCount > 0 ? ($totalRoas / $roasCount) : 0.0;

            return ServiceReturn::success(data: [
                'spend' => $totalSpend,
                'impressions' => $totalImpressions,
                'clicks' => $totalClicks,
                'conversions' => $totalConversions,
                'roas' => $avgRoas,
            ]);
        } catch (\Throwable $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    protected function convertDatePresetToRange(string $datePreset): ?array
    {
        $today = Carbon::today();

        return match ($datePreset) {
            'maximum' => null,
            'today' => [
                'start' => $today,
                'end' => $today,
            ],
            'yesterday' => [
                'start' => $today->copy()->subDay(),
                'end' => $today->copy()->subDay(),
            ],
            'last_7d' => [
                'start' => $today->copy()->subDays(7),
                'end' => $today,
            ],
            'last_30d' => [
                'start' => $today->copy()->subDays(30),
                'end' => $today,
            ],
            'last_90d' => [
                'start' => $today->copy()->subDays(90),
                'end' => $today,
            ],
            default => null,
        };
    }

    /**
     * Lấy bảng xếp hạng chi tiêu các tài khoản Google Ads
     */
    public function getAccountSpendingRanking(?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
    {
        try {
            // Query insights từ database, group by account và sum spend
            $query = $this->googleAdsAccountInsightRepository->query()
                ->select('google_account_id', DB::raw('SUM(spend::numeric) as total_spend'))
                ->groupBy('google_account_id');

            // Filter theo date range nếu có
            if ($startDate) {
                $query->where('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }

            // Chỉ lấy accounts có spend > 0
            $query->havingRaw('SUM(spend::numeric) > 0');

            $insights = $query->get();

            // Lấy thông tin accounts
            $accountIds = $insights->pluck('google_account_id')->toArray();
            if (empty($accountIds)) {
                return ServiceReturn::success(data: []);
            }

            $accounts = $this->googleAccountRepository->query()
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id');

            // Tạo ranking list
            $ranking = [];
            foreach ($insights as $insight) {
                $account = $accounts->get($insight->google_account_id);
                if (!$account) {
                    continue;
                }

                $statusEnum = GoogleCustomerStatus::tryFrom((int) $account->account_status);
                $statusLabel = $statusEnum?->label() ?? __('common.unknown');

                $ranking[] = [
                    'account_id' => (string) $account->id,
                    'account_name' => $account->account_name ?? $account->account_id,
                    'account_id_display' => $account->account_id,
                    'account_status' => $account->account_status,
                    'status_label' => $statusLabel,
                    'total_spend' => (float) $insight->total_spend,
                ];
            }

            // Sắp xếp theo spend giảm dần
            usort($ranking, function ($a, $b) {
                return $b['total_spend'] <=> $a['total_spend'];
            });

            // Thêm rank
            foreach ($ranking as $index => &$item) {
                $item['rank'] = $index + 1;
            }

            return ServiceReturn::success(data: $ranking);
        } catch (\Throwable $exception) {
            Logging::error(
                message: "Error get account spending ranking: " . $exception->getMessage(),
                exception: $exception,
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy dữ liệu báo cáo tổng hợp cho dịch vụ Google Ads
    public function getReportData(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $serviceUsers = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->with('package')
                ->get();

            $googleServiceUsers = $serviceUsers->filter(function ($serviceUser) {
                return $serviceUser->package->platform === PlatformType::GOOGLE->value;
            });

            if ($googleServiceUsers->isEmpty()) {
                return ServiceReturn::success(data: [
                    'total_spend' => 0,
                    'today_spend' => 0,
                    'account_spend' => [],
                ]);
            }

            $googleAccounts = $this->googleAccountRepository->query()
                ->whereIn('service_user_id', $googleServiceUsers->pluck('id'))
                ->get();

            $googleAccountIds = $googleAccounts->pluck('id')->toArray();
            if (empty($googleAccountIds)) {
                return ServiceReturn::success(data: [
                    'total_spend' => 0,
                    'today_spend' => 0,
                    'account_spend' => [],
                ]);
            }

            $totalResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'maximum');
            if ($totalResult->isError()) {
                return ServiceReturn::error(message: $totalResult->getMessage());
            }

            $todayResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'today');
            if ($todayResult->isError()) {
                return ServiceReturn::error(message: $todayResult->getMessage());
            }

            $spendByAccount = $this->googleAdsAccountInsightRepository->query()
                ->whereIn('google_account_id', $googleAccountIds)
                ->select('google_account_id', DB::raw('SUM(spend::numeric) as total_spend'))
                ->groupBy('google_account_id')
                ->get()
                ->keyBy('google_account_id');

            $accountSpend = $googleAccounts->map(function ($account) use ($spendByAccount) {
                $record = $spendByAccount->get($account->id);
                $total = $record ? (float) $record->total_spend : 0.0;
                return [
                    'account_id' => $account->account_id,
                    'account_name' => $account->account_name ?? $account->account_id,
                    'amount_spent' => $total,
                ];
            })->values()->toArray();

            return ServiceReturn::success(data: [
                'total_spend' => $totalResult->getData()['spend'] ?? 0,
                'today_spend' => $todayResult->getData()['spend'] ?? 0,
                'account_spend' => $accountSpend,
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'GoogleAdsService@getReportData error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy dữ liệu biểu đồ báo cáo theo khoảng thời gian
     */
    public function getReportInsights(string $datePreset): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $serviceUserIds = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->whereHas('package', function ($query) {
                    $query->where('platform', PlatformType::GOOGLE->value);
                })
                ->pluck('id')
                ->toArray();

            if (empty($serviceUserIds)) {
                return ServiceReturn::success(data: [
                    'total_spend_period' => 0,
                    'chart' => [],
                ]);
            }

            $endDate = Carbon::today();
            $startDate = match ($datePreset) {
                'last_7d' => Carbon::today()->subDays(6),
                'last_14d' => Carbon::today()->subDays(13),
                'last_28d' => Carbon::today()->subDays(27),
                'last_30d' => Carbon::today()->subDays(29),
                'last_90d' => Carbon::today()->subDays(89),
                default => Carbon::today()->subDays(6),
            };

            $records = $this->googleAdsAccountInsightRepository->query()
                ->whereIn('service_user_id', $serviceUserIds)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get([
                    'date',
                    DB::raw('SUM(spend::numeric) as total_spend'),
                ])
                ->keyBy('date');

            $chartData = [];
            foreach ($records as $record) {
                $chartData[] = [
                    'value' => (float) $record->total_spend,
                    'date' => $record->date->format('Y-m-d'),
                ];
            }

            return ServiceReturn::success(data: [
                'total_spend_period' => collect($chartData)->sum('value'),
                'chart' => $chartData,
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'GoogleAdsService@getReportInsights error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1, '.', '') . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1, '.', '') . 'K';
        }

        return (string) $number;
    }

    public function syncGoogleAccounts(ServiceUser $serviceUser): ServiceReturn
    {
        try {
            $customerIds = $this->extractGoogleCustomerIds($serviceUser);

            Logging::web('GoogleAdsService@syncGoogleAccounts: Extracted customer IDs', [
                'service_user_id' => $serviceUser->id,
                'customer_ids_count' => count($customerIds),
                'customer_ids' => $customerIds,
            ]);

            if (empty($customerIds)) {
                $config = $serviceUser->config_account ?? [];
                $managerId = null;
                if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                    // lấy BM ID đầu tiên từ account đầu tiên
                    $firstAccount = $config['accounts'][0];
                    if (isset($firstAccount['bm_ids']) && is_array($firstAccount['bm_ids']) && !empty($firstAccount['bm_ids'])) {
                        $managerId = $firstAccount['bm_ids'][0];
                    }
                } else {
                    $managerId = Arr::get($config, 'google_manager_id') ?? Arr::get($config, 'bm_id');
                }
                $platformConfig = $this->getPlatformConfig();

                Logging::error(
                    message: 'GoogleAdsService@syncGoogleAccounts missing customer ids',
                    context: [
                        'service_user_id' => $serviceUser->id,
                        'manager_id' => $managerId,
                        'has_oauth_config' => !empty($platformConfig['client_id']) && !empty($platformConfig['client_secret']) && !empty($platformConfig['refresh_token']),
                        'config_account' => $config,
                    ]
                );

                return ServiceReturn::error(message: __('google_ads.error.missing_customer_ids'));
            }

            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                Logging::error(
                    message: 'GoogleAdsService@syncGoogleAccounts missing login customer id',
                    context: [
                        'service_user_id' => $serviceUser->id,
                        'config_account' => $serviceUser->config_account,
                    ]
                );
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }

            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $query = <<<GAQL
SELECT
  customer.id,
  customer.descriptive_name,
  customer.currency_code,
  customer.time_zone,
  customer.status
FROM customer
GAQL;

            $syncedCount = 0;
            foreach ($customerIds as $customerId) {
                try {
                    $request = new SearchGoogleAdsStreamRequest([
                        'customer_id' => (string) $customerId,
                        'query' => $query,
                    ]);
                    $stream = $googleAdsService->searchStream($request);
                    $accountCount = 0;
                    foreach ($stream->readAll() as $response) {
                        foreach ($response->getResults() as $row) {
                            $customer = $row->getCustomer();
                            $accountId = $customer->getId() ?? $this->extractIdFromResource($customer->getResourceName());
                            if (!$accountId) {
                                continue;
                            }

                            $mappedStatus = $this->mapStatusToInt($customer->getStatus());

                            // Lấy balance từ cache hoặc query từ API
                            $balanceData = $this->getAccountBalance($googleAdsService, (string) $accountId, (string) $customerId);
                            $balance = $balanceData['balance'] ?? null;
                            $balanceExhausted = $balanceData['exhausted'] ?? false;

                            $this->googleAccountRepository->query()->updateOrCreate(
                                [
                                    'service_user_id' => $serviceUser->id,
                                    'account_id' => (string) $accountId,
                                ],
                                [
                                    'account_name' => $customer->getDescriptiveName(),
                                    'account_status' => $mappedStatus,
                                    'currency' => $customer->getCurrencyCode(),
                                    'customer_manager_id' => $loginCustomerId,
                                    'time_zone' => $customer->getTimeZone(),
                                    'balance' => $balance,
                                    'balance_exhausted' => $balanceExhausted,
                                    'last_synced_at' => now(),
                                ]
                            );
                            $accountCount++;
                            $syncedCount++;
                        }
                    }

                    Logging::web('GoogleAdsService@syncGoogleAccounts: Synced customer', [
                        'service_user_id' => $serviceUser->id,
                        'customer_id' => $customerId,
                        'accounts_synced' => $accountCount,
                    ]);
                } catch (GoogleAdsException|ApiException $exception) {
                    Logging::error(
                        message: 'GoogleAdsService@syncGoogleAccounts failed',
                        context: [
                            'service_user_id' => $serviceUser->id,
                            'customer_id' => $customerId,
                            'error' => $exception->getMessage(),
                        ],
                        exception: $exception
                    );
                }
            }

            Logging::web('GoogleAdsService@syncGoogleAccounts: Completed', [
                'service_user_id' => $serviceUser->id,
                'total_customer_ids' => count($customerIds),
                'total_accounts_synced' => $syncedCount,
            ]);

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'GoogleAdsService@syncGoogleAccounts unexpected error',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
            return ServiceReturn::error(message: __('google_ads.error.sync_failed'));
        }
    }

    public function syncGoogleCampaigns(ServiceUser $serviceUser): ServiceReturn
    {
        try {
            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }

            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $this->googleAccountRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->chunkById(10, function (Collection $accounts) use ($googleAdsService, $serviceUser) {
                    foreach ($accounts as $account) {
                        $this->syncCampaignsForAccount($googleAdsService, $serviceUser, $account->id, $account->account_id);
                    }
                });

            $campaignIds = $this->googleAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->pluck('id');
            foreach ($campaignIds as $campaignId) {
                Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN, (string) $campaignId);
            }

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'GoogleAdsService@syncGoogleCampaigns failed',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
            return ServiceReturn::error(message: __('google_ads.error.sync_failed'));
        }
    }

    public function syncGoogleInsights(ServiceUser $serviceUser): ServiceReturn
    {
        try {
            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }

            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $this->googleAccountRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->chunkById(10, function (Collection $accounts) use ($googleAdsService, $serviceUser) {
                    foreach ($accounts as $account) {
                        $this->syncInsightsForAccount($googleAdsService, $serviceUser, $account->id, $account->account_id);
                    }
                });

            Caching::clearCache(CacheKey::CACHE_GOOGLE_DASHBOARD, (string) $serviceUser->user_id);

            $campaignIds = $this->googleAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->pluck('id');
            foreach ($campaignIds as $campaignId) {
                Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN, (string) $campaignId);
                Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_INSIGHT, (string) $campaignId . 'last_7d');
                Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_INSIGHT, (string) $campaignId . 'last_30d');
            }

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'GoogleAdsService@syncGoogleInsights failed',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
            return ServiceReturn::error(message: __('google_ads.error.sync_failed'));
        }
    }

    protected function syncCampaignsForAccount(
        GoogleAdsServiceClient $googleAdsService,
        ServiceUser $serviceUser,
        string $googleAccountDbId,
        string $googleAccountId
    ): void {
        $query = <<<GAQL
SELECT
  campaign.id,
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type,
  campaign.start_date,
  campaign.end_date
FROM campaign
WHERE campaign.status != REMOVED
ORDER BY campaign.id
GAQL;

        try {
            $request = new SearchGoogleAdsStreamRequest([
                'customer_id' => (string) $googleAccountId,
                'query' => $query,
            ]);

            $campaignCount = 0;
            $stream = $googleAdsService->searchStream($request);
            foreach ($stream->readAll() as $response) {
                foreach ($response->getResults() as $row) {
                    $this->persistCampaignRow($serviceUser, $googleAccountDbId, $googleAccountId, $row);
                    $campaignCount++;
                }
            }

            \Illuminate\Support\Facades\Log::info(
                'GoogleAdsService@syncCampaignsForAccount: Successfully synced campaigns',
                [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                    'campaign_count' => $campaignCount,
                ]
            );
        } catch (GoogleAdsException|ApiException $exception) {
            Logging::error(
                message: 'GoogleAdsService@syncCampaignsForAccount failed',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                    'error' => $exception->getMessage(),
                    'query' => $query,
                ],
                exception: $exception
            );
        }
    }

    protected function persistCampaignRow(
        ServiceUser $serviceUser,
        string $googleAccountDbId,
        string $googleAccountId,
        GoogleAdsRow $row
    ): void {
        $campaign = $row->getCampaign();
        if (!$campaign) {
            Logging::error(
                message: 'GoogleAdsService@persistCampaignRow: Campaign is null',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                ]
            );
            return;
        }

        $campaignId = (string) $campaign->getId();
        if (!$campaignId) {
            Logging::error(
                message: 'GoogleAdsService@persistCampaignRow: Campaign ID is null',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                ]
            );
            return;
        }

        // Budget sẽ được lấy từ campaign_budget resource riêng nếu cần
        // Hiện tại để null, có thể query riêng sau nếu cần
        $dailyBudget = null;

        $startDate = $campaign->getStartDate();
        $endDate = $campaign->getEndDate();

        // Xử lý start_date và end_date (format: YYYYMMDD hoặc null)
        $startTime = null;
        $stopTime = null;

        if ($startDate) {
            try {
                // Google Ads API trả về start_date dạng string "YYYYMMDD" hoặc null
                // Nếu là string rỗng, bỏ qua
                if (is_string($startDate) && strlen($startDate) === 8) {
                    $startTime = Carbon::createFromFormat('Ymd', $startDate);
                } elseif (is_string($startDate) && strlen($startDate) > 0) {
                    // Thử parse với format khác nếu cần
                    $startTime = Carbon::parse($startDate);
                }
            } catch (\Exception $e) {
                // Log để debug nhưng không throw error
                \Illuminate\Support\Facades\Log::info(
                    'GoogleAdsService@persistCampaignRow: Could not parse start_date',
                    [
                        'campaign_id' => $campaignId,
                        'start_date' => $startDate,
                        'start_date_type' => gettype($startDate),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        if ($endDate) {
            try {
                // Google Ads API trả về end_date dạng string "YYYYMMDD" hoặc null
                if (is_string($endDate) && strlen($endDate) === 8) {
                    $stopTime = Carbon::createFromFormat('Ymd', $endDate);
                } elseif (is_string($endDate) && strlen($endDate) > 0) {
                    $stopTime = Carbon::parse($endDate);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::info(
                    'GoogleAdsService@persistCampaignRow: Could not parse end_date',
                    [
                        'campaign_id' => $campaignId,
                        'end_date' => $endDate,
                        'end_date_type' => gettype($endDate),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        // Get account status for comparison
        $googleAccount = $this->googleAccountRepository->find($googleAccountDbId);
        $normalizedCampaignStatus = $this->normalizeGoogleCampaignStatus($campaign->getStatus());

        try {
            $this->googleAdsCampaignRepository->query()->updateOrCreate(
                [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountDbId,
                    'campaign_id' => $campaignId,
                ],
                [
                    'name' => $campaign->getName() ?? '',
                    'status' => $normalizedCampaignStatus,
                    'effective_status' => $normalizedCampaignStatus,
                    'objective' => $campaign->getAdvertisingChannelType()?->name ?? null,
                    'daily_budget' => $dailyBudget ? (string) $dailyBudget : null,
                    'budget_remaining' => null, // Google Ads không có budget_remaining trong campaign query
                    'start_time' => $startTime,
                    'stop_time' => $stopTime,
                    'last_synced_at' => now(),
                ]
            );
        } catch (\Exception $exception) {
            Logging::error(
                message: 'GoogleAdsService@persistCampaignRow failed',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                    'campaign_id' => $campaignId,
                    'campaign_name' => $campaign->getName(),
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
        }
    }

    protected function normalizeGoogleCampaignStatus(?int $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return GoogleCampaignStatus::fromApiStatus($status)->value;
    }

    protected function syncInsightsForAccount(
        GoogleAdsServiceClient $googleAdsService,
        ServiceUser $serviceUser,
        string $googleAccountDbId,
        string $googleAccountId
    ): void {
        $query = <<<GAQL
SELECT
  segments.date,
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.conversions,
  metrics.ctr,
  metrics.average_cpc,
  metrics.average_cpm,
  metrics.conversions_value
FROM customer
WHERE segments.date DURING LAST_30_DAYS
GAQL;

        try {
            $request = new SearchGoogleAdsStreamRequest([
                'customer_id' => (string) $googleAccountId,
                'query' => $query,
            ]);
            $stream = $googleAdsService->searchStream($request);
            foreach ($stream->readAll() as $response) {
                foreach ($response->getResults() as $row) {
                    $this->persistInsightRow($serviceUser, $googleAccountDbId, $googleAccountId, $row);
                }
            }
        } catch (GoogleAdsException|ApiException $exception) {
            Logging::error(
                message: 'GoogleAdsService@syncInsightsForAccount failed',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $googleAccountId,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    protected function persistInsightRow(
        ServiceUser $serviceUser,
        string $googleAccountDbId,
        string $googleAccountId,
        GoogleAdsRow $row
    ): void {
        $segments = $row->getSegments();
        $metrics = $row->getMetrics();
        $date = $segments->getDate();
        if (!$date) {
            return;
        }

        $spend = $this->convertMicrosToUnit($metrics->getCostMicros());
        $impressions = (int) ($metrics->getImpressions() ?? 0);
        $clicks = (int) ($metrics->getClicks() ?? 0);
        $conversions = (int) ($metrics->getConversions() ?? 0);
        $conversionsValue = $metrics->getConversionsValue() ?? 0.0;
        $roas = $spend > 0 ? $conversionsValue / max($spend, 0.000001) : 0.0;

        $payload = [
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'ctr' => (float) ($metrics->getCtr() ?? 0),
            'cpc' => $this->convertMicrosToUnit($metrics->getAverageCpc()),
            'cpm' => $this->convertMicrosToUnit($metrics->getAverageCpm()),
            'conversion_actions' => [
                'conversions_value' => $conversionsValue,
            ],
            'roas' => $roas,
            'last_synced_at' => now(),
        ];

        $this->googleAdsAccountInsightRepository->query()->updateOrCreate(
            [
                'service_user_id' => $serviceUser->id,
                'google_account_id' => $googleAccountDbId,
                'date' => $date,
            ],
            $payload
        );
    }

    /**
     * Lấy cấu hình Google Ads từ database (platform_settings)
     * Fallback về .env nếu không tìm thấy trong database
     * @return array
     * @throws \RuntimeException
     */
    protected function getPlatformConfig(): array
    {
        // Cache config trong request để tránh query nhiều lần
        if ($this->platformConfig !== null) {
            return $this->platformConfig;
        }

        try {
            // Ưu tiên lấy từ database
            $platformSetting = $this->platformSettingService->findPlatformActive(
                platform: PlatformType::GOOGLE->value
            );

            if ($platformSetting->isSuccess()) {
                $platformData = $platformSetting->getData();
                $config = $platformData->config ?? [];

                // Validate config từ database
                if (!empty($config['developer_token']) &&
                    !empty($config['client_id']) &&
                    !empty($config['client_secret']) &&
                    !empty($config['refresh_token'])) {
                    $this->platformConfig = [
                        'developer_token' => $config['developer_token'],
                        'client_id' => $config['client_id'],
                        'client_secret' => $config['client_secret'],
                        'refresh_token' => $config['refresh_token'],
                        'login_customer_id' => $config['login_customer_id'] ?? null,
                        'linked_customer_id' => $config['linked_customer_id'] ?? null,
                        'customer_ids' => $config['customer_ids'] ?? null,
                    ];
                    return $this->platformConfig;
                }
            }
        } catch (\Throwable $e) {
            Logging::error(
                message: 'GoogleAdsService@getPlatformConfig: Failed to get config from database, falling back to .env',
                context: [
                    'error' => $e->getMessage(),
                ],
                exception: $e
            );
        }

        // Fallback về .env nếu không tìm thấy trong database hoặc config không đầy đủ
        $this->platformConfig = [
            'developer_token' => config('googleads.developer_token'),
            'client_id' => config('googleads.client_id'),
            'client_secret' => config('googleads.client_secret'),
            'refresh_token' => config('googleads.refresh_token'),
            'login_customer_id' => config('googleads.login_customer_id'),
            'linked_customer_id' => config('googleads.linked_customer_id'),
            'customer_ids' => null,
        ];

        return $this->platformConfig;
    }

    /**
     * Khởi tạo Google Ads Client từ cấu hình hệ thống
     * Ưu tiên lấy từ database (platform_settings), fallback về .env
     */
    protected function buildGoogleAdsClient(?string $loginCustomerId = null, ?string $linkedCustomerId = null): GoogleAdsClient
    {
        $config = $this->getPlatformConfig();

        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $refreshToken = $config['refresh_token'];
        $developerToken = $config['developer_token'];

        // Validate OAuth credentials
        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            $missing = [];
            if (empty($clientId)) $missing[] = 'client_id';
            if (empty($clientSecret)) $missing[] = 'client_secret';
            if (empty($refreshToken)) $missing[] = 'refresh_token';

            Logging::error(
                message: 'GoogleAdsService@buildGoogleAdsClient: Missing OAuth credentials',
                context: [
                    'missing_credentials' => $missing,
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret),
                    'has_refresh_token' => !empty($refreshToken),
                    'source' => 'database_or_env',
                ]
            );

            throw new \RuntimeException(
                'Thiếu thông tin xác thực OAuth: ' . implode(', ', $missing) . '. ' .
                'Vui lòng cấu hình trong "Cấu hình nền tảng" hoặc kiểm tra lại file .env.'
            );
        }

        if (empty($developerToken)) {
            Logging::error(
                message: 'GoogleAdsService@buildGoogleAdsClient: Missing developer token',
                context: [
                    'source' => 'database_or_env',
                ]
            );
            throw new \RuntimeException(
                'Thiếu Developer Token. Vui lòng cấu hình trong "Cấu hình nền tảng" hoặc kiểm tra lại file .env.'
            );
        }

        try {
            $oAuthCredential = (new OAuth2TokenBuilder())
                ->withClientId($clientId)
                ->withClientSecret($clientSecret)
                ->withRefreshToken($refreshToken)
                ->build();

            $builder = (new GoogleAdsClientBuilder())
                ->withDeveloperToken($developerToken)
                ->withOAuth2Credential($oAuthCredential);

            // Ưu tiên loginCustomerId từ parameter, sau đó từ config, cuối cùng từ .env
            if ($loginCustomerId = $loginCustomerId ?? $config['login_customer_id']) {
                $builder = $builder->withLoginCustomerId($loginCustomerId);
            }

            if ($linkedCustomerId = $linkedCustomerId ?? $config['linked_customer_id']) {
                $builder = $builder->withLinkedCustomerId($linkedCustomerId);
            }

            return $builder->build();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'GoogleAdsService@buildGoogleAdsClient: Failed to build Google Ads client',
                context: [
                    'error' => $exception->getMessage(),
                    'error_class' => get_class($exception),
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret),
                    'has_refresh_token' => !empty($refreshToken),
                    'has_developer_token' => !empty($developerToken),
                    'source' => 'database_or_env',
                ],
                exception: $exception
            );

            // Nếu là lỗi OAuth, cung cấp hướng dẫn cụ thể
            if (str_contains($exception->getMessage(), 'invalid_client') ||
                str_contains($exception->getMessage(), 'Unauthorized')) {
                throw new \RuntimeException(
                    'Lỗi xác thực OAuth với Google Ads API. ' .
                    'Có thể do: (1) Client ID/Secret không đúng, (2) Refresh token không hợp lệ hoặc đã hết hạn, ' .
                    '(3) OAuth credentials chưa được cấu hình đúng trong Google Cloud Console. ' .
                    'Vui lòng kiểm tra lại credentials trong "Cấu hình nền tảng" hoặc .env và tạo lại refresh token nếu cần. ' .
                    'Chi tiết lỗi: ' . $exception->getMessage()
                );
            }

            throw $exception;
        }
    }

    protected function convertMicrosToUnit(?int $micros): float
    {
        if (!$micros) {
            return 0.0;
        }
        return round($micros / 1_000_000, 6);
    }

    protected function extractGoogleCustomerIds(ServiceUser $serviceUser): array
    {
        $config = $serviceUser->config_account ?? [];
        $ids = Arr::get($config, 'google_customer_ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }
        if (!is_array($ids)) {
            $ids = [];
        }
        if (empty($ids) && isset($config['google_customer_id'])) {
            $ids = [(string) $config['google_customer_id']];
        }

        if (empty($ids)) {
            $ids = $this->fetchCustomerIdsFromManager($serviceUser);
        }

        return array_values(array_filter(array_map(
            fn ($id) => preg_replace('/[^0-9]/', '', (string) $id),
            $ids
        )));
    }

    protected function fetchCustomerIdsFromManager(ServiceUser $serviceUser): array
    {
        $config = $serviceUser->config_account ?? [];
        $managerId = null;
        if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
            $firstAccount = $config['accounts'][0];
            if (isset($firstAccount['bm_ids']) && is_array($firstAccount['bm_ids']) && !empty($firstAccount['bm_ids'])) {
                $managerId = $firstAccount['bm_ids'][0];
            }
        } else {
            $managerId = Arr::get($config, 'google_manager_id') ?? Arr::get($config, 'bm_id');
        }
        if (!$managerId) {
            Logging::error(
                message: 'GoogleAdsService@fetchCustomerIdsFromManager: No manager ID found in config',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'config_account' => $config,
                ]
            );
            return [];
        }

        try {
            $client = $this->buildGoogleAdsClient($managerId);
            /** @var GoogleAdsServiceClient $googleAdsService */
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $query = <<<GAQL
SELECT
  customer_client.client_customer,
  customer_client.manager,
  customer_client.descriptive_name,
  customer_client.status
FROM customer_client
WHERE customer_client.manager = FALSE
  AND customer_client.status != 'CANCELED'
GAQL;

            $request = new SearchGoogleAdsStreamRequest([
                'customer_id' => (string) $managerId,
                'query' => $query,
            ]);

            $customerIds = [];
            $customerDetails = [];
            $stream = $googleAdsService->searchStream($request);
            foreach ($stream->readAll() as $response) {
                /** @var GoogleAdsRow $row */
                foreach ($response->getResults() as $row) {
                    $customerClient = $row->getCustomerClient();
                    $clientCustomer = $customerClient?->getClientCustomer();
                    if ($clientCustomer) {
                        $customerId = $this->extractIdFromResource($clientCustomer);
                        $customerIds[] = $customerId;
                        $customerDetails[] = [
                            'id' => $customerId,
                            'name' => $customerClient->getDescriptiveName() ?? 'N/A',
                            'status' => $customerClient->getStatus() ?? 'UNKNOWN',
                        ];
                    }
                }
            }

            return array_values(array_filter($customerIds));
        } catch (\RuntimeException $exception) {
            // Lỗi từ buildGoogleAdsClient (missing credentials, invalid OAuth, etc.)
            Logging::error(
                message: 'GoogleAdsService@fetchCustomerIdsFromManager: Failed to build client or authenticate',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'manager_id' => $managerId,
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
            return [];
        } catch (GoogleAdsException|ApiException $exception) {
            Logging::error(
                message: 'GoogleAdsService@fetchCustomerIdsFromManager: Google Ads API error',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'manager_id' => $managerId,
                    'error' => $exception->getMessage(),
                    'error_code' => method_exists($exception, 'getCode') ? $exception->getCode() : null,
                ],
                exception: $exception
            );
            return [];
        } catch (\Exception $exception) {
            Logging::error(
                message: 'GoogleAdsService@fetchCustomerIdsFromManager: Unexpected error',
                context: [
                    'service_user_id' => $serviceUser->id,
                    'manager_id' => $managerId,
                    'error' => $exception->getMessage(),
                    'error_class' => get_class($exception),
                ],
                exception: $exception
            );
            return [];
        }
    }

    protected function extractIdFromResource(?string $resourceName): ?string
    {
        if (!$resourceName) {
            return null;
        }
        return Str::afterLast($resourceName, '/');
    }

    protected function fetchCampaignAggregateMetrics(
        GoogleAdsServiceClient $googleAdsService,
        string $customerId,
        string $campaignId,
        string $dateRange
    ): array {
        $query = <<<GAQL
SELECT
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.conversions,
  metrics.ctr,
  metrics.average_cpc,
  metrics.average_cpm,
  metrics.conversions_value
FROM campaign
WHERE campaign.id = {$campaignId}
  AND segments.date DURING {$dateRange}
GAQL;

        $spend = 0.0;
        $impressions = 0;
        $clicks = 0;
        $conversions = 0;
        $ctr = 0.0;
        $avgCpc = 0.0;
        $avgCpm = 0.0;
        $conversionsValue = 0.0;

        $request = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query,
        ]);

        foreach ($googleAdsService->searchStream($request)->readAll() as $response) {
            foreach ($response->getResults() as $row) {
                $metrics = $row->getMetrics();
                if (!$metrics) {
                    continue;
                }
                $spend += $this->convertMicrosToUnit($metrics->getCostMicros());
                $impressions += (int) ($metrics->getImpressions() ?? 0);
                $clicks += (int) ($metrics->getClicks() ?? 0);
                $conversions += (int) ($metrics->getConversions() ?? 0);
                $ctr = (float) ($metrics->getCtr() ?? $ctr);
                $avgCpc = $this->convertMicrosToUnit($metrics->getAverageCpc()) ?: $avgCpc;
                $avgCpm = $this->convertMicrosToUnit($metrics->getAverageCpm()) ?: $avgCpm;
                $conversionsValue += (float) ($metrics->getConversionsValue() ?? 0);
            }
        }

        $roas = $spend > 0 ? $conversionsValue / max($spend, 0.000001) : 0.0;

        return [
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'ctr' => $ctr,
            'cpc' => $avgCpc,
            'cpm' => $avgCpm,
            'roas' => $roas,
        ];
    }

    protected function fetchCampaignDailyMetrics(
        GoogleAdsServiceClient $googleAdsService,
        string $customerId,
        string $campaignId,
        string $dateRange
    ): array {
        $query = <<<GAQL
SELECT
  segments.date,
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.conversions,
  metrics.ctr,
  metrics.average_cpc,
  metrics.average_cpm,
  metrics.conversions_value
FROM campaign
WHERE campaign.id = {$campaignId}
  AND segments.date DURING {$dateRange}
ORDER BY segments.date
GAQL;

        $request = new SearchGoogleAdsStreamRequest([
            'customer_id' => $customerId,
            'query' => $query,
        ]);

        $result = [];
        foreach ($googleAdsService->searchStream($request)->readAll() as $response) {
            foreach ($response->getResults() as $row) {
                $segments = $row->getSegments();
                $metrics = $row->getMetrics();
                if (!$segments || !$metrics) {
                    continue;
                }
                $date = $segments->getDate();
                if (!$date) {
                    continue;
                }
                $spend = $this->convertMicrosToUnit($metrics->getCostMicros());
                $conversionsValue = (float) ($metrics->getConversionsValue() ?? 0);
                $roas = $spend > 0 ? $conversionsValue / max($spend, 0.000001) : 0.0;

                $result[] = [
                    'date' => $date,
                    'spend' => $spend,
                    'impressions' => (int) ($metrics->getImpressions() ?? 0),
                    'clicks' => (int) ($metrics->getClicks() ?? 0),
                    'conversions' => (int) ($metrics->getConversions() ?? 0),
                    'ctr' => (float) ($metrics->getCtr() ?? 0),
                    'cpc' => $this->convertMicrosToUnit($metrics->getAverageCpc()),
                    'cpm' => $this->convertMicrosToUnit($metrics->getAverageCpm()),
                    'roas' => $roas,
                ];
            }
        }

        return $result;
    }

    protected function resolveLoginCustomerId(ServiceUser $serviceUser): ?string
    {
        $config = $serviceUser->config_account ?? [];
        $id = Arr::get($config, 'google_manager_id');
        if ($id) {
            return preg_replace('/[^0-9]/', '', (string) $id);
        }

        // Lấy từ platform config (database hoặc .env)
        $platformConfig = $this->getPlatformConfig();
        return $platformConfig['login_customer_id'];
    }

    protected function mapStatusToInt(mixed $status): int
    {
        return GoogleCustomerStatus::fromApiStatus($status)->value;
    }

    /**
     * Lấy balance của account từ Google Ads API (từ account_budget)
     *
     * Logic:
     * - Query từ account_budget với approved_spending_limit_micros (Giới hạn chi tiêu) và amount_served_micros (Số tiền đã tiêu)
     * - Tính số dư: approved_spending_limit_micros - amount_served_micros
     * - Nếu số dư <= 0 → balance_exhausted = true
     *
     * Lưu ý: Chỉ hoạt động với tài khoản trả trước (Manual Payments/Prepay)
     * Với tài khoản trả sau (Automatic Payments), approved_spending_limit_type sẽ là INFINITE
     *
     * @param GoogleAdsServiceClient $googleAdsService
     * @param string $accountId
     * @param string $customerId
     * @return array ['balance' => float|null, 'exhausted' => bool]
     */
    protected function getAccountBalance(
        GoogleAdsServiceClient $googleAdsService,
        string $accountId,
        string $customerId
    ): array {
        $balance = null;
        $balanceExhausted = false;

        try {
            // Query từ account_budget để lấy giới hạn chi tiêu và số tiền đã tiêu
            $accountBudgetQuery = <<<GAQL
SELECT
  account_budget.id,
  account_budget.approved_spending_limit_micros,
  account_budget.amount_served_micros,
  account_budget.approved_spending_limit_type
FROM account_budget
WHERE
  account_budget.status = 'APPROVED'
ORDER BY account_budget.id DESC
LIMIT 1
GAQL;

            $request = new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $accountBudgetQuery,
            ]);

            $stream = $googleAdsService->searchStream($request);
            foreach ($stream->readAll() as $response) {
                foreach ($response->getResults() as $row) {
                    $accountBudget = $row->getAccountBudget();
                    if (!$accountBudget) {
                        continue;
                    }

                    // Lấy giới hạn chi tiêu và số tiền đã tiêu
                    $approvedLimitMicros = $accountBudget->getApprovedSpendingLimitMicros();
                    $amountServedMicros = $accountBudget->getAmountServedMicros();
                    $limitType = $accountBudget->getApprovedSpendingLimitType();

                    // Chỉ tính balance cho tài khoản trả trước (SPECIFIC_AMOUNT)
                    // Tài khoản trả sau (INFINITE) không có balance cố định
                    $limitTypeName = null;
                    if ($limitType !== null) {
                        // Enum có thể là object, string, hoặc int
                        if (is_object($limitType)) {
                            if (method_exists($limitType, 'getName')) {
                                $limitTypeName = $limitType->getName();
                            } elseif (method_exists($limitType, 'name')) {
                                $limitTypeName = $limitType->name;
                            } elseif (property_exists($limitType, 'name')) {
                                $limitTypeName = $limitType->name;
                            }
                        } elseif (is_string($limitType)) {
                            $limitTypeName = $limitType;
                        } elseif (is_int($limitType)) {
                            // Nếu là int, có thể là enum value, map sang tên
                            // Google Ads BudgetDeliveryMethod enum: SPECIFIC_AMOUNT = 1, INFINITE = 2
                            $limitTypeName = match($limitType) {
                                1 => 'SPECIFIC_AMOUNT',
                                2 => 'INFINITE',
                                default => null,
                            };
                        }
                    }

                    if ($limitTypeName === 'INFINITE') {
                        // Tài khoản trả sau, không có balance cố định
                        $balance = null;
                        $balanceExhausted = false;

                        Logging::web('GoogleAdsService@getAccountBalance: Account is INFINITE (Automatic Payments)', [
                            'account_id' => $accountId,
                            'customer_id' => $customerId,
                            'limit_type' => 'INFINITE',
                        ]);
                        break;
                    }

                    // Chuyển đổi từ micros sang đơn vị tiền tệ (1 đơn vị = 1,000,000 micros)
                    $approvedLimit = $this->convertMicrosToUnit($approvedLimitMicros);
                    $amountServed = $this->convertMicrosToUnit($amountServedMicros);

                    // Tính số dư còn lại
                    $balance = $approvedLimit !== null && $amountServed !== null
                        ? max(0, $approvedLimit - $amountServed)
                        : null;

                    // Nếu số dư <= 0 hoặc không tính được, đánh dấu là exhausted
                    $balanceExhausted = $balance === null || $balance <= 0;

                    Logging::web('GoogleAdsService@getAccountBalance: Fetched from account_budget', [
                        'account_id' => $accountId,
                        'customer_id' => $customerId,
                        'approved_limit_micros' => $approvedLimitMicros,
                        'amount_served_micros' => $amountServedMicros,
                        'approved_limit' => $approvedLimit,
                        'amount_served' => $amountServed,
                        'balance' => $balance,
                        'balance_exhausted' => $balanceExhausted,
                        'limit_type' => $limitTypeName,
                    ]);
                    break;
                }
            }
        } catch (GoogleAdsException|ApiException $exception) {
            // Nếu không có quyền truy cập account_budget hoặc lỗi API, log và để null
            Logging::error(
                message: 'GoogleAdsService@getAccountBalance: Cannot fetch balance from account_budget',
                context: [
                    'account_id' => $accountId,
                    'customer_id' => $customerId,
                    'error' => $exception->getMessage(),
                ],
                exception: $exception
            );
            // Để balance = null và balance_exhausted = false (sẽ cần cập nhật thủ công)
        }

        return [
            'balance' => $balance,
            'exhausted' => $balanceExhausted,
        ];
    }

    /**
     * Validate service user
     * @param string $serviceUserId
     * @return ServiceReturn
     */
    protected function validateServiceUser(string $serviceUserId): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->find($serviceUserId);
            // validate service user tồn tại
            if (!$serviceUser) {
                return ServiceReturn::error(__('google_ads.error.service_not_found'));
            }
            // validate dịch vụ là Google Ads
            if ($serviceUser->package->platform !== PlatformType::GOOGLE->value) {
                return ServiceReturn::error(__('google_ads.error.service_user_platform_not_google'));
            }
            // validate phân quyền
            /** @var \App\Models\User $user */
            $user = Auth::user();
            switch ($user->role) {
                case UserRole::ADMIN->value:
                    // Admin thì không cần kiểm tra gì thêm
                    break;
                case UserRole::MANAGER->value:
                    // manager sử lý sau, hiện tại cho chung logic với employee
                case UserRole::EMPLOYEE->value:
                    // Kiểm tra xem có phải dịch vụ thuộc user mà mình quản lý không
                    $isReferralService = $user->referrals()
                        ->where('referred_id', $serviceUser->user_id)
                        ->exists();
                    if (!$isReferralService) {
                        return ServiceReturn::error(__('google_ads.error.service_not_found'));
                    }
                    break;
                case UserRole::AGENCY->value:
                    // Kiểm tra xem có phải dịch vụ của mình không
                    $isOwnService = $serviceUser->user_id == $user->id;
                    // Kiểm tra xem có phải dịch vụ thuộc user mà mình quản lý không
                    $isReferralService = $user->referrals()
                        ->where('referred_id', $serviceUser->user_id)
                        ->exists();
                    if (!$isOwnService && !$isReferralService) {
                        return ServiceReturn::error(__('google_ads.error.service_not_found'));
                    }
                    break;
                case UserRole::CUSTOMER->value:
                    // Kiểm tra xem có phải dịch vụ của mình không
                    $isOwnService = $serviceUser->user_id == $user->id;
                    if (!$isOwnService) {
                        return ServiceReturn::error(__('google_ads.error.service_not_found'));
                    }
                    break;
                default:
                    return ServiceReturn::error(__('google_ads.error.service_not_found'));
            }
            return ServiceReturn::success(data: $serviceUser);
        } catch (\Exception $e) {
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách chiến dịch quảng cáo theo service user và account id, có phân trang
     * @param string $serviceUserId
     * @param string $accountId
     * @param QueryListDTO $queryListDTO
     * @return ServiceReturn
     */
    public function getCampaignsPaginatedByServiceUserIdAndAccountId(string $serviceUserId, string $accountId, QueryListDTO $queryListDTO): ServiceReturn
    {
        // validate service user
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /**
         * @var ServiceUser $serviceUser
         */
        $serviceUser = $serviceUserResult->getData();
        try {
            // get Google Ads Account
            $adsAccount = $this->googleAccountRepository->query()
                ->where('id', $accountId)
                ->where('service_user_id', $serviceUser->id)
                ->first();
            if (!$adsAccount) {
                return ServiceReturn::error(__('google_ads.error.account_not_found'));
            }

            // Lấy insights từ database để tính spend
            $googleAccountIds = [$adsAccount->id];

            // Maximum insights (tổng spend)
            $totalResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'maximum');
            $totalSpend = $totalResult->isSuccess() ? (float) ($totalResult->getData()['spend'] ?? 0) : 0.0;

            // Today insights
            $todayResult = $this->getAccountsInsightsSummaryFromDatabase($googleAccountIds, 'today');
            $todaySpend = $todayResult->isSuccess() ? (float) ($todayResult->getData()['spend'] ?? 0) : 0.0;

            // Query campaigns từ database
            $query = $this->googleAdsCampaignRepository->query();
            $query = $this->googleAdsCampaignRepository->filterQuery(
                $query,
                [
                    'service_user_id' => $serviceUser->id,
                    'google_account_id' => $accountId,
                ]);
            $query = $this->googleAdsCampaignRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);

            // Gán spend vào mỗi campaign (hiện tại lấy từ account level, có thể cải thiện sau để lấy từ campaign level)
            $paginator->getCollection()->each(function ($item) use ($totalSpend, $todaySpend) {
                $item->total_spend = $totalSpend;
                $item->today_spend = $todaySpend;
            });

            $campaignSnapshot = $paginator->getCollection()
                ->map(function ($campaign) {
                    return [
                        'id' => (string) $campaign->id,
                        'campaign_id' => $campaign->campaign_id,
                        'status' => $campaign->status,
                        'effective_status' => $campaign->effective_status,
                    ];
                })
                ->take(20)
                ->values()
                ->toArray();

            Logging::web('GoogleAdsService@getCampaignsPaginated status snapshot', [
                'service_user_id' => (string) $serviceUser->id,
                'google_account_id' => (string) $accountId,
                'total' => $paginator->total(),
                'snapshot' => $campaignSnapshot,
            ]);

            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách chiến dịch quảng cáo GoogleAdsService@getCampaignsPaginatedByServiceUserIdAndAccountId: ' . $e->getMessage(),
                exception: $e
            );
            // trả về paginator rỗng khi có lỗi
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $queryListDTO->perPage,
                    currentPage: $queryListDTO->page
                )
            );
        }
    }

    /**
     * Lấy danh sách tài khoản quảng cáo theo service user, có phân trang
     * @param string $serviceUserId
     * @param QueryListDTO $queryListDTO
     * @return ServiceReturn
     */
    public function getAdsAccountPaginatedByServiceUserId(string $serviceUserId, QueryListDTO $queryListDTO): ServiceReturn
    {
        // validate service user
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /**
         * @var ServiceUser $serviceUser
         */
        $serviceUser = $serviceUserResult->getData();
        try {
            $query = $this->googleAccountRepository->query();
            $query = $this->googleAccountRepository->filterQuery(
                $query,
                [
                    'service_user_id' => $serviceUser->id,
                ]);
            $query = $this->googleAccountRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);

            $accountSnapshot = $paginator->getCollection()
                ->map(function ($account) {
                    $statusEnum = GoogleCustomerStatus::tryFrom((int) $account->account_status);
                    return [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_status' => $account->account_status,
                        'status_label' => $statusEnum?->label() ?? null,
                    ];
                })
                ->take(20)
                ->values()
                ->toArray();

            Logging::web('GoogleAdsService@getAdsAccountPaginated status snapshot', [
                'service_user_id' => (string) $serviceUser->id,
                'total' => $paginator->total(),
                'snapshot' => $accountSnapshot,
            ]);
            return ServiceReturn::success(data: $paginator);
        }
        catch (\Exception $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách tài khoản quảng cáo GoogleAdsService@getAdsAccountPaginatedByServiceUser: ' . $e->getMessage(),
                exception: $e
            );
            // trả về paginator rỗng khi có lỗi
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $queryListDTO->perPage,
                    currentPage: $queryListDTO->page
                )
            );
        }
    }

    /**
     * Lấy thông tin chi tiết chiến dịch Google Ads
     * @param string $serviceUserId
     * @param string $campaignId
     * @return ServiceReturn
     */
    public function getCampaignDetail(string $serviceUserId, string $campaignId): ServiceReturn
    {
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }

        /** @var ServiceUser $serviceUser */
        $serviceUser = $serviceUserResult->getData();

        try {
            $data = Caching::getCache(
                key: CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN,
                uniqueKey: $campaignId,
            );
            if ($data) {
                return ServiceReturn::success(data: $data);
            }

            $campaign = $this->googleAdsCampaignRepository->query()
                ->with('googleAccount')
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('google_ads.error.campaign_not_found'));
            }
            $googleAccount = $campaign->googleAccount;
            if (!$googleAccount || empty($googleAccount->account_id)) {
                return ServiceReturn::error(message: __('google_ads.error.account_not_found'));
            }

            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }
            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $todayMetrics = $this->fetchCampaignAggregateMetrics(
                $googleAdsService,
                (string) $googleAccount->account_id,
                (string) $campaign->campaign_id,
                'TODAY'
            );
            // Dùng khoảng 30 ngày gần nhất làm dữ liệu “tổng quan” (thay vì 7 ngày như trước khiến tổng = 0 nếu 7 ngày không chi tiêu)
            $last30dMetrics = $this->fetchCampaignAggregateMetrics(
                $googleAdsService,
                (string) $googleAccount->account_id,
                (string) $campaign->campaign_id,
                'LAST_30_DAYS'
            );

            $data = [
                'id' => $campaign->id,
                'service_user_id' => $campaign->service_user_id,
                'google_account_id' => $campaign->google_account_id,
                'campaign_id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'effective_status' => $campaign->effective_status,
                'objective' => $campaign->objective,
                'daily_budget' => $campaign->daily_budget,
                'budget_remaining' => $campaign->budget_remaining,
                'start_time' => $campaign->start_time?->toIso8601String(),
                'stop_time' => $campaign->stop_time?->toIso8601String(),
                'last_synced_at' => $campaign->last_synced_at?->toIso8601String(),
                'today_spend' => $todayMetrics['spend'] ?? 0.0,
                'total_spend' => $last30dMetrics['spend'] ?? 0.0,
                'cpc_avg' => $last30dMetrics['cpc'] ?? 0.0,
                'cpm_avg' => $last30dMetrics['cpm'] ?? 0.0,
                'roas_avg' => $last30dMetrics['roas'] ?? 0.0,
                'insight' => [
                    'spend' => [
                        'today' => $todayMetrics['spend'] ?? 0.0,
                        'total' => $last30dMetrics['spend'] ?? 0.0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['spend'] ?? 0.0,
                            $todayMetrics['spend'] ?? 0.0
                        ),
                    ],
                    'impressions' => [
                        'today' => $todayMetrics['impressions'] ?? 0,
                        'total' => $last30dMetrics['impressions'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['impressions'] ?? 0,
                            $todayMetrics['impressions'] ?? 0
                        ),
                    ],
                    'clicks' => [
                        'today' => $todayMetrics['clicks'] ?? 0,
                        'total' => $last30dMetrics['clicks'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['clicks'] ?? 0,
                            $todayMetrics['clicks'] ?? 0
                        ),
                    ],
                    'cpc' => [
                        'today' => $todayMetrics['cpc'] ?? 0.0,
                        'total' => $last30dMetrics['cpc'] ?? 0.0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['cpc'] ?? 0.0,
                            $todayMetrics['cpc'] ?? 0.0
                        ),
                    ],
                    'cpm' => [
                        'today' => $todayMetrics['cpm'] ?? 0.0,
                        'total' => $last30dMetrics['cpm'] ?? 0.0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['cpm'] ?? 0.0,
                            $todayMetrics['cpm'] ?? 0.0
                        ),
                    ],
                    'conversions' => [
                        'today' => $todayMetrics['conversions'] ?? 0,
                        'total' => $last30dMetrics['conversions'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $last30dMetrics['conversions'] ?? 0,
                            $todayMetrics['conversions'] ?? 0
                        ),
                    ],
                ],
            ];

            Caching::setCache(
                key: CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN,
                value: $data,
                uniqueKey: $campaignId,
                expire: 15
            );

            return ServiceReturn::success(data: $data);
        } catch (\Throwable $exception) {
            $errorMessage = $exception->getMessage();

            // Kiểm tra lỗi OAuth token expired/revoked
            if (
                str_contains($errorMessage, 'invalid_grant') ||
                str_contains($errorMessage, 'Token has been expired') ||
                str_contains($errorMessage, 'Token has been revoked')
            ) {
                // Clear platform config cache để force reload credentials
                $this->clearPlatformConfigCache();

                Logging::error(
                    message: 'GoogleAdsService@getCampaignDetail: OAuth token expired or revoked, cleared config cache',
                    context: [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaignId,
                        'error' => $errorMessage,
                    ],
                    exception: $exception
                );
                return ServiceReturn::error(message: __('google_ads.error.oauth_token_expired'));
            }

            Logging::error(
                message: 'GoogleAdsService@getCampaignDetail failed',
                context: [
                    'service_user_id' => $serviceUserId,
                    'campaign_id' => $campaignId,
                    'error' => $errorMessage,
                ],
                exception: $exception
            );
            return ServiceReturn::error(message: __('google_ads.error.failed_to_fetch_campaign_detail'));
        }
    }

    /**
     * Cập nhật trạng thái chiến dịch Google Ads (PAUSED / ENABLED / REMOVED).
     */
    public function updateCampaignStatus(string $serviceUserId, string $campaignId, string $status): ServiceReturn
    {
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /** @var ServiceUser $serviceUser */
        $serviceUser = $serviceUserResult->getData();

        try {
            $campaign = $this->googleAdsCampaignRepository->query()
                ->with('googleAccount')
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('google_ads.error.campaign_not_found'));
            }
            $googleAccount = $campaign->googleAccount;
            if (!$googleAccount || empty($googleAccount->account_id)) {
                return ServiceReturn::error(message: __('google_ads.error.account_not_found'));
            }

            $campaignStatus = GoogleCampaignStatus::fromValue($status);
            if (!$campaignStatus) {
                return ServiceReturn::error(message: __('google_ads.error.invalid_campaign_status'));
            }

            $statusMap = [
                GoogleCampaignStatus::ENABLED->value => CampaignStatus::ENABLED,
                GoogleCampaignStatus::PAUSED->value => CampaignStatus::PAUSED,
                GoogleCampaignStatus::REMOVED->value => CampaignStatus::REMOVED,
            ];
            $normalizedStatus = $campaignStatus->value;

            // Kiểm tra validation khi resume (ENABLED) cho Customer/Agency
            if ($campaignStatus === GoogleCampaignStatus::ENABLED) {
                $user = Auth::user();
                $userRole = $user->role ?? null;

                // Admin, Manager, Employee không cần kiểm tra
                if (in_array($userRole, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                    // Cho phép cập nhập trạng thái của campaign
                } elseif (in_array($userRole, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                    // Customer/Agency: Kiểm tra spending > balance + 100
                    $balance = (float) ($googleAccount->balance ?? 0);
                    $threshold = 100.0;

                    // Lấy chi tiêu tích lũy từ database
                    $insightsResult = $this->getAccountsInsightsSummaryFromDatabase(
                        [(string) $googleAccount->id],
                        'maximum'
                    );

                    if (!$insightsResult->isError()) {
                        $lifetimeSpending = (float) ($insightsResult->getData()['spend'] ?? 0);
                        $thresholdAmount = $balance + $threshold;

                        // Nếu chi tiêu vượt quá số dư + ngưỡng, không cho phép resume
                        if ($lifetimeSpending > $thresholdAmount) {
                            return ServiceReturn::error(
                                message: __('google_ads.error.cannot_resume_spending_exceeded', [
                                    'spending' => number_format($lifetimeSpending, 2),
                                    'balance' => number_format($balance, 2),
                                    'threshold' => number_format($thresholdAmount, 2),
                                ])
                            );
                        }
                    }
                }
            }

            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }

            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $campaignService = $client->getCampaignServiceClient();

            $resourceName = ResourceNames::forCampaign((string) $googleAccount->account_id, (string) $campaign->campaign_id);

            $campaignResource = new Campaign();
            $campaignResource->setResourceName($resourceName);
            $campaignResource->setStatus($statusMap[$normalizedStatus]);

            $operation = new CampaignOperation();
            $operation->setUpdate($campaignResource);
            // Chỉ update trường status
            $operation->setUpdateMask((new \Google\Protobuf\FieldMask(['paths' => ['status']])));

            $request = MutateCampaignsRequest::build((string) $googleAccount->account_id, [$operation]);
            $response = $campaignService->mutateCampaigns($request);

            // Cập nhật lại local status để UI sync nhanh hơn
            $campaign->status = $normalizedStatus;
            $campaign->effective_status = $normalizedStatus;
            $campaign->save();

            Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN, (string) $campaignId);

            return ServiceReturn::success(data: [
                'resource_name' => $response->getResults()[0]->getResourceName(),
                'status' => $normalizedStatus,
            ]);
        } catch (GoogleAdsException|ApiException $e) {
            Logging::error(
                message: 'GoogleAdsService@updateCampaignStatus error: '.$e->getMessage(),
                context: [
                    'service_user_id' => $serviceUserId,
                    'campaign_id' => $campaignId,
                    'google_account_id' => $googleAccount->account_id ?? null,
                    'login_customer_id' => $loginCustomerId ?? null,
                ],
                exception: $e,
            );

            $rawMessage = $e->getMessage() ?? '';
            if (str_contains($rawMessage, 'ACTION_NOT_PERMITTED_FOR_SUSPENDED_ACCOUNT')) {
                // Tài khoản Google Ads đang bị suspend, báo message thân thiện hơn
                return ServiceReturn::error(
                    message: __('google_ads.error.failed_to_update_campaign_status_suspended')
                );
            }

            return ServiceReturn::error(
                message: __('google_ads.error.failed_to_update_campaign_status')
            );
        } catch (\Throwable $e) {
            Logging::error(
                message: 'GoogleAdsService@updateCampaignStatus unexpected error: '.$e->getMessage(),
                context: [
                    'service_user_id' => $serviceUserId,
                    'campaign_id' => $campaignId,
                ],
                exception: $e,
            );
            $message = __('common_error.server_error');
            if (config('app.debug')) {
                $message .= ' (Google: '.$e->getMessage().')';
            }
            return ServiceReturn::error(message: $message);
        }
    }

    /**
     * Cập nhật ngân sách (amount_micros trong CampaignBudget) cho chiến dịch Google Ads.
     *
     * Lưu ý:
     * - Trong Google Ads, ngân sách hầu hết được quản lý qua CampaignBudget (có thể được shared cho nhiều campaign).
     * - Hàm này sẽ:
     *   + Lấy campaign.campaign_budget của campaign hiện tại;
     *   + Gọi CampaignBudgetService::mutateCampaignBudgets để cập nhật amount_micros.
     * - Nếu nhiều campaign dùng chung một CampaignBudget, việc cập nhật sẽ ảnh hưởng tới tất cả các campaign đó.
     */
    public function updateCampaignDailyBudget(string $serviceUserId, string $campaignId, float $amount): ServiceReturn
    {
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /** @var ServiceUser $serviceUser */
        $serviceUser = $serviceUserResult->getData();

        if ($amount <= 0) {
            return ServiceReturn::error(message: __('google_ads.error.invalid_budget_amount'));
        }

        try {
            $campaign = $this->googleAdsCampaignRepository->query()
                ->with('googleAccount')
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('google_ads.error.campaign_not_found'));
            }
            $googleAccount = $campaign->googleAccount;
            if (!$googleAccount || empty($googleAccount->account_id)) {
                return ServiceReturn::error(message: __('google_ads.error.account_not_found'));
            }

            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }

            $client = $this->buildGoogleAdsClient($loginCustomerId);

            $customerId = (string) $googleAccount->account_id;
            $googleAdsService = $client->getGoogleAdsServiceClient();

            // 1. Lấy campaign.campaign_budget để biết resource name của CampaignBudget
            $query = sprintf(
                'SELECT campaign.id, campaign.campaign_budget FROM campaign WHERE campaign.id = %s LIMIT 1',
                $campaign->campaign_id
            );

            try {
                // Dùng SearchGoogleAdsRequest để tránh cảnh báo kiểu dữ liệu
                $searchRequest = new \Google\Ads\GoogleAds\V22\Services\SearchGoogleAdsRequest([
                    'customer_id' => $customerId,
                    'query' => $query,
                ]);
                $response = $googleAdsService->search($searchRequest);
            } catch (GoogleAdsException|ApiException $e) {
                Logging::error(
                    message: 'GoogleAdsService@updateCampaignDailyBudget: failed to fetch campaign_budget',
                    context: [
                        'service_user_id' => $serviceUserId,
                        'campaign_db_id' => $campaignId,
                        'campaign_id' => $campaign->campaign_id,
                        'google_account_id' => $customerId,
                        'error' => $e->getMessage(),
                    ],
                    exception: $e,
                );

                return ServiceReturn::error(
                    message: __('google_ads.error.failed_to_update_campaign_budget')
                );
            }

            $iterator = $response->getIterator();
            if (!$iterator->valid()) {
                return ServiceReturn::error(
                    message: __('google_ads.error.failed_to_update_campaign_budget')
                );
            }

            /** @var \Google\Ads\GoogleAds\V22\Services\GoogleAdsRow $row */
            $row = $iterator->current();
            $gaCampaign = $row->getCampaign();
            if (!$gaCampaign || !$gaCampaign->getCampaignBudget()) {
                return ServiceReturn::error(
                    message: __('google_ads.error.failed_to_update_campaign_budget')
                );
            }

            $campaignBudgetResourceName = $gaCampaign->getCampaignBudget();

            // 2. Gọi CampaignBudgetService để cập nhật amount_micros
            $budgetService = $client->getCampaignBudgetServiceClient();

            $budget = new CampaignBudget();
            $budget->setResourceName($campaignBudgetResourceName);
            $budget->setAmountMicros((int) round($amount * 1_000_000));

            $operation = new CampaignBudgetOperation();
            $operation->setUpdate($budget);
            $operation->setUpdateMask(new \Google\Protobuf\FieldMask([
                'paths' => ['amount_micros'],
            ]));

            $request = MutateCampaignBudgetsRequest::build($customerId, [$operation]);
            $response = $budgetService->mutateCampaignBudgets($request);

            Caching::clearCache(CacheKey::CACHE_DETAIL_GOOGLE_CAMPAIGN, (string) $campaignId);

            return ServiceReturn::success(data: [
                'resource_name' => $response->getResults()[0]->getResourceName() ?? $campaignBudgetResourceName,
                'daily_budget' => $amount,
            ]);

        } catch (\Throwable $e) {
            Logging::error(
                message: 'GoogleAdsService@updateCampaignDailyBudget unexpected error: '.$e->getMessage(),
                exception: $e,
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy thông tin chi tiết về hiệu suất quảng cáo Google Ads cho một chiến dịch cụ thể
     * @param string $serviceUserId
     * @param string $campaignId
     * @param string $datePreset ('last_7d', 'last_30d')
     * @return ServiceReturn
     */
    public function getCampaignDailyInsights(string $serviceUserId, string $campaignId, string $datePreset): ServiceReturn
    {
        // validate service user
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /**
         * @var ServiceUser $serviceUser
         */
        $serviceUser = $serviceUserResult->getData();

        try {
            if (!in_array($datePreset, ['last_7d', 'last_30d'])) {
                return ServiceReturn::error(message: __('google_ads.error.date_preset_invalid'));
            }

            $cacheKey = $campaignId . $datePreset;
            $data = Caching::getCache(
                key: CacheKey::CACHE_DETAIL_GOOGLE_INSIGHT,
                uniqueKey: $cacheKey,
            );
            if ($data) {
                return ServiceReturn::success(data: $data);
            }

            $campaign = $this->googleAdsCampaignRepository->query()
                ->with('googleAccount')
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('google_ads.error.campaign_not_found'));
            }
            $googleAccount = $campaign->googleAccount;
            if (!$googleAccount || empty($googleAccount->account_id)) {
                return ServiceReturn::error(message: __('google_ads.error.account_not_found'));
            }

            $loginCustomerId = $this->resolveLoginCustomerId($serviceUser);
            if (empty($loginCustomerId)) {
                return ServiceReturn::error(message: __('google_ads.error.no_manager_id_found'));
            }
            $client = $this->buildGoogleAdsClient($loginCustomerId);
            $googleAdsService = $client->getGoogleAdsServiceClient();

            $gaqlPreset = $datePreset === 'last_7d' ? 'LAST_7_DAYS' : 'LAST_30_DAYS';
            $insights = $this->fetchCampaignDailyMetrics(
                $googleAdsService,
                (string) $googleAccount->account_id,
                (string) $campaign->campaign_id,
                $gaqlPreset
            );

            Caching::setCache(
                key: CacheKey::CACHE_DETAIL_GOOGLE_INSIGHT,
                value: $insights,
                uniqueKey: $cacheKey,
                expire: 15
            );

            return ServiceReturn::success(data: $insights);
        } catch (\Throwable $exception) {
            $errorMessage = $exception->getMessage();

            // Kiểm tra lỗi OAuth token expired/revoked
            if (
                str_contains($errorMessage, 'invalid_grant') ||
                str_contains($errorMessage, 'Token has been expired') ||
                str_contains($errorMessage, 'Token has been revoked')
            ) {
                // Clear platform config cache để force reload credentials
                $this->clearPlatformConfigCache();

                Logging::error(
                    message: 'GoogleAdsService@getCampaignDailyInsights: OAuth token expired or revoked, cleared config cache',
                    context: [
                        'service_user_id' => $serviceUserId,
                        'campaign_id' => $campaignId,
                        'date_preset' => $datePreset,
                        'error' => $errorMessage,
                    ],
                    exception: $exception
                );
                return ServiceReturn::error(message: __('google_ads.error.oauth_token_expired'));
            }

            Logging::error(
                message: 'GoogleAdsService@getCampaignDailyInsights failed',
                context: [
                    'service_user_id' => $serviceUserId,
                    'campaign_id' => $campaignId,
                    'date_preset' => $datePreset,
                    'error' => $errorMessage,
                ],
                exception: $exception
            );
            return ServiceReturn::error(message: __('google_ads.error.failed_to_fetch_campaign_detail'));
        }
    }

    /**
     * Kiểm tra và tự động tạm dừng tài khoản nếu spending > balance + threshold
     * @param float $threshold Ngưỡng cảnh báo (mặc định 100 USD)
     * @return ServiceReturn
     */
    public function checkAndAutoPauseAccounts(float $threshold = 100.0): ServiceReturn
    {
        try {
            $accounts = $this->googleAccountRepository->query()
                ->with(['serviceUser.user'])
                ->whereNotNull('balance')
                ->where('balance', '>', 0)
                ->get();

            $paused = 0;
            $notified = 0;
            $errors = 0;

            foreach ($accounts as $account) {
                try {
                    $balance = (float) ($account->balance ?? 0);

                    // Kiểm tra nếu số dư thấp hơn ngưỡng an toàn (cảnh báo số dư thấp)
                    if ($balance < $threshold) {
                        // Pause tất cả campaigns trong account
                        $campaigns = $this->googleAdsCampaignRepository->query()
                            ->where('google_account_id', $account->id)
                            ->where('status', '!=', GoogleCampaignStatus::PAUSED->value)
                            ->where('status', '!=', GoogleCampaignStatus::REMOVED->value)
                            ->get();

                        foreach ($campaigns as $campaign) {
                            if ($account->serviceUser) {
                                $pauseResult = $this->updateCampaignStatus(
                                    (string) $account->serviceUser->id,
                                    (string) $campaign->id,
                                    GoogleCampaignStatus::PAUSED->value
                                );
                                if ($pauseResult->isError()) {
                                    Logging::web('GoogleAdsService@checkAndAutoPauseAccounts: Failed to pause campaign', [
                                        'account_id' => $account->id,
                                        'campaign_id' => $campaign->id,
                                        'error' => $pauseResult->getMessage(),
                                    ]);
                                }
                            }
                        }

                        $paused++;

                        // Gửi thông báo số dư thấp
                        // Vì đây là trường hợp balance < threshold, chưa chắc đã chi tiêu vượt quá
                        $notificationResult = $this->googleAdsNotificationService->sendLowBalanceAlert(
                            $account,
                            $threshold
                        );
                        if ($notificationResult->isSuccess()) {
                            $notified++;
                        }

                        Logging::web('GoogleAdsService@checkAndAutoPauseAccounts: Auto-paused account (low balance)', [
                            'account_id' => $account->id,
                            'account_name' => $account->account_name,
                            'balance' => $balance,
                            'threshold' => $threshold,
                            'campaigns_paused' => $campaigns->count(),
                            'notification_sent' => $notificationResult->isSuccess(),
                        ]);
                        continue;
                    }

                    // Kiểm tra nếu chi tiêu tích lũy (lifetime) > balance + threshold
                    // Lưu ý: Google Ads API không có date preset "maximum" hay "lifetime"
                    // Nên lấy tổng chi tiêu từ database (tất cả insights đã sync)
                    // Đây là tổng chi tiêu từ khi bắt đầu sync, không phải lifetime thực từ API
                    $insightsResult = $this->getAccountsInsightsSummaryFromDatabase(
                        [(string) $account->id],
                        'maximum' // 'maximum' = không filter date, lấy tất cả từ database
                    );

                    if ($insightsResult->isError()) {
                        $errors++;
                        continue;
                    }

                    $lifetimeSpending = (float) ($insightsResult->getData()['spend'] ?? 0);
                    $thresholdAmount = $balance + $threshold;

                    // Kiểm tra nếu chi tiêu tích lũy vượt quá số dư + ngưỡng an toàn
                    if ($lifetimeSpending > $thresholdAmount) {
                        // Pause tất cả campaigns trong account
                        $campaigns = $this->googleAdsCampaignRepository->query()
                            ->where('google_account_id', $account->id)
                            ->where('status', '!=', GoogleCampaignStatus::PAUSED->value)
                            ->where('status', '!=', GoogleCampaignStatus::REMOVED->value)
                            ->get();

                        foreach ($campaigns as $campaign) {
                            if ($account->serviceUser) {
                                $pauseResult = $this->updateCampaignStatus(
                                    (string) $account->serviceUser->id,
                                    (string) $campaign->id,
                                    GoogleCampaignStatus::PAUSED->value
                                );
                                if ($pauseResult->isError()) {
                                    Logging::web('GoogleAdsService@checkAndAutoPauseAccounts: Failed to pause campaign', [
                                        'account_id' => $account->id,
                                        'campaign_id' => $campaign->id,
                                        'error' => $pauseResult->getMessage(),
                                    ]);
                                }
                            }
                        }

                        $paused++;

                        // Gửi thông báo
                        $notificationResult = $this->googleAdsNotificationService->sendSpendingExceededAlert(
                            $account,
                            $lifetimeSpending,
                            $threshold
                        );
                        if ($notificationResult->isSuccess()) {
                            $notified++;
                        }

                        Logging::web('GoogleAdsService@checkAndAutoPauseAccounts: Auto-paused account (spending exceeded)', [
                            'account_id' => $account->id,
                            'account_name' => $account->account_name,
                            'balance' => $balance,
                            'lifetime_spending' => $lifetimeSpending,
                            'threshold' => $thresholdAmount,
                            'campaigns_paused' => $campaigns->count(),
                            'notification_sent' => $notificationResult->isSuccess(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    Logging::error(
                        message: 'GoogleAdsService@checkAndAutoPauseAccounts: Error processing account',
                        context: [
                            'account_id' => $account->id,
                            'error' => $e->getMessage(),
                        ],
                        exception: $e
                    );
                }
            }

            return ServiceReturn::success(data: [
                'paused' => $paused,
                'notified' => $notified,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'GoogleAdsService@checkAndAutoPauseAccounts: Unexpected error',
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}

