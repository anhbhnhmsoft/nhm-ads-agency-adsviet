<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\Meta\MetaAdsAccountStatus;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Repositories\MetaAccountRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\MetaBusinessManagerRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\WalletRepository;
use App\Common\Constants\Config\ConfigName;
use Carbon\Carbon;
use FacebookAds\Object\Values\AdDatePresetValues;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service phục vụ các thao tác liên quan đến Meta (Facebook)
 */
class MetaService
{
    public function __construct(
        protected MetaBusinessService       $metaBusinessService,
        protected MetaAccountRepository     $metaAccountRepository,
        protected MetaAdsCampaignRepository $metaAdsCampaignRepository,
        protected ServiceUserRepository     $serviceUserRepository,
        protected MetaAdsAccountInsightRepository $metaAdsAccountInsightRepository,
        protected WalletRepository          $walletRepository,
        protected MetaAdsNotificationService $metaAdsNotificationService,
        protected MetaBusinessManagerRepository $metaBusinessManagerRepository,
        protected ConfigService             $configService,
    )
    {
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
                return ServiceReturn::error(__('meta.error.service_not_found'));
            }
            // validate dịch vụ là Meta
            if ($serviceUser->package->platform !== PlatformType::META->value) {
                return ServiceReturn::error(__('meta.error.service_user_platform_not_meta'));
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
                        return ServiceReturn::error(__('meta.error.service_not_found'));
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
                        return ServiceReturn::error(__('meta.error.service_not_found'));
                    }
                    break;
                case UserRole::CUSTOMER->value:
                    // Kiểm tra xem có phải dịch vụ của mình không
                    $isOwnService = $serviceUser->user_id == $user->id;
                    if (!$isOwnService) {
                        return ServiceReturn::error(__('meta.error.service_not_found'));
                    }
                    break;
                default:
                    return ServiceReturn::error(__('meta.error.service_not_found'));
            }
            return ServiceReturn::success(data: $serviceUser);
        } catch (\Exception $e) {
            return ServiceReturn::error(__('common_error.server_error'));
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
            $query = $this->metaAccountRepository->query();
            $query = $this->metaAccountRepository->filterQuery(
                $query,
                [
                    'service_user_id' => $serviceUser->id,
                ]);
            $query = $this->metaAccountRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);

            $accountSnapshot = $paginator->getCollection()
                ->map(function ($account) {
                    $statusEnum = MetaAdsAccountStatus::tryFrom((int) $account->account_status);
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
            return ServiceReturn::success(data: $paginator);
        }
        catch (\Exception $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách tài khoản quảng cáo MetaService@getAdsAccountPaginatedByServiceUser: ' . $e->getMessage(),
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
            // get Ads Account
            $adsAccount = $this->metaAccountRepository->query()
                ->where('id', $accountId)
                ->where('service_user_id', $serviceUser->id)
                ->first();
            if (!$adsAccount) {
                return ServiceReturn::error(__('meta.error.account_not_found'));
            }
            // -----------------------------------------------------------------
            // BƯỚC 1: GỌI 2 API INSIGHTS (NHƯNG KHÔNG CHẶN NẾU LỖI)
            // -----------------------------------------------------------------
            $totalData = ['data' => []];
            $todayData = ['data' => []];

            // Lần 1: Lấy TỔNG CHI TIÊU
            try {
                $totalResult = $this->metaBusinessService->getAccountInsightsByCampaign(
                    accountId: $adsAccount->account_id,
                    datePreset: 'maximum',
                    fields: ['campaign_id', 'spend']
                );
                if ($totalResult->isSuccess()) {
                    $totalData = $totalResult->getData();
                } else {
                    Logging::error(
                        message: 'MetaService@getCampaignsPaginated: failed total insights, fallback to 0',
                        context: [
                            'service_user_id' => $serviceUser->id,
                            'meta_account_id' => $accountId,
                            'error' => $totalResult->getMessage(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Logging::error(
                    message: 'MetaService@getCampaignsPaginated: exception total insights, fallback to 0',
                    context: [
                        'service_user_id' => $serviceUser->id,
                        'meta_account_id' => $accountId,
                        'error' => $e->getMessage(),
                    ],
                    exception: $e
                );
            }

            // Lần 2: Lấy CHI TIÊU HÔM NAY (Mới)
            try {
                $todayResult = $this->metaBusinessService->getAccountInsightsByCampaign(
                    accountId: $adsAccount->account_id,
                    datePreset: 'today',
                    fields: ['campaign_id', 'spend']
                );
                if ($todayResult->isSuccess()) {
                    $todayData = $todayResult->getData();
                } else {
                    Logging::error(
                        message: 'MetaService@getCampaignsPaginated: failed today insights, fallback to 0',
                        context: [
                            'service_user_id' => $serviceUser->id,
                            'meta_account_id' => $accountId,
                            'error' => $todayResult->getMessage(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Logging::error(
                    message: 'MetaService@getCampaignsPaginated: exception today insights, fallback to 0',
                    context: [
                        'service_user_id' => $serviceUser->id,
                        'meta_account_id' => $accountId,
                        'error' => $e->getMessage(),
                    ],
                    exception: $e
                );
            }
            // -----------------------------------------------------------------
            // BƯỚC 2: TẠO MAP ĐỂ TRA CỨU
            // -----------------------------------------------------------------
            // Tổng chi tiêu
            $totalSpendMap = [];
            foreach ($totalData['data'] ?? [] as $insight) {
                $totalSpendMap[$insight['campaign_id']] = $insight['spend'] ?? 0;
            }
            // Chi tiêu hôm nay
            $todaySpendMap = [];
            foreach ($todayData['data'] ?? [] as $insight) {
                $todaySpendMap[$insight['campaign_id']] = $insight['spend'] ?? 0;
            }
            // -----------------------------------------------------------------
            // BƯỚC 3: TRUY VẤN CSDL
            // -----------------------------------------------------------------
            $query = $this->metaAdsCampaignRepository->query();
            $query = $this->metaAdsCampaignRepository->filterQuery(
                $query,
                [
                    'service_user_id' => $serviceUser->id,
                    'meta_account_id' => $accountId,
                ]);
            $query = $this->metaAdsCampaignRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);

            // -----------------------------------------------------------------
            // BƯỚC 4: GÁN DỮ LIỆU VÀO PAGINATOR
            // -----------------------------------------------------------------
            $paginator->getCollection()->each(function ($item) use ($totalSpendMap, $todaySpendMap) {
                $item->total_spend = $totalSpendMap[$item->campaign_id] ?? 0;
                $item->today_spend = $todaySpendMap[$item->campaign_id] ?? 0;
            });
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách chiến dịch quảng cáo MetaService@getCampaigns: ' . $exception->getMessage(),
                exception: $exception
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
     * Cập nhật trạng thái chiến dịch Meta theo service user + campaign DB id.
     * @param string $serviceUserId
     * @param string $campaignId
     * @param string $status ACTIVE|PAUSED|DELETED
     * @return ServiceReturn
     */
    public function updateCampaignStatus(string $serviceUserId, string $campaignId, string $status): ServiceReturn
    {
        // validate service user
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /** @var ServiceUser $serviceUser */
        $serviceUser = $serviceUserResult->getData();

        try {
            $campaign = $this->metaAdsCampaignRepository->query()
                ->with('metaAccount')
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();

            if (!$campaign) {
                return ServiceReturn::error(message: __('meta.error.campaign_not_found'));
            }

            $normalizedStatus = strtoupper($status);
            $metaAccount = $campaign->metaAccount;

            // Kiểm tra validation khi resume (ACTIVE) cho Customer/Agency
            if ($normalizedStatus === 'ACTIVE' && $metaAccount) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $userRole = $user->role ?? null;

                // Admin, Manager, Employee không cần kiểm tra
                if (in_array($userRole, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                    // Cho phép resume
                } elseif (in_array($userRole, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                    // Customer/Agency: Kiểm tra spending > balance + threshold (lấy từ config)
                    $balance = (float) ($metaAccount->balance ?? 0);
                    $threshold = (float) $this->configService->getValue(ConfigName::THRESHOLD_PAUSE, 100);

                    // Lấy chi tiêu tích lũy (lifetime)
                    $lifetimeSpending = (float) ($metaAccount->amount_spent ?? 0);

                    // Nếu không có amount_spent, lấy từ insights database
                    if ($lifetimeSpending == 0) {
                        $insightsResult = $this->getAccountsInsightsSummaryFromDatabase(
                            [(string) $metaAccount->id],
                            'maximum'
                        );
                        if (!$insightsResult->isError()) {
                            $lifetimeSpending = (float) ($insightsResult->getData()['spend'] ?? 0);
                        }
                    }

                    $thresholdAmount = $balance + $threshold;

                    // Nếu chi tiêu vượt quá số dư + ngưỡng, không cho phép resume
                    if ($lifetimeSpending > $thresholdAmount) {
                        return ServiceReturn::error(
                            message: __('meta.error.cannot_resume_spending_exceeded', [
                                'spending' => number_format($lifetimeSpending, 2),
                                'balance' => number_format($balance, 2),
                                'threshold' => number_format($thresholdAmount, 2),
                            ])
                        );
                    }
                }
            }

            $apiResult = $this->metaBusinessService->updateCampaignStatus($campaign->campaign_id, $status);
            if ($apiResult->isError()) {
                return $apiResult;
            }

            // Cập nhật trạng thái local (để UI đỡ lệch quá nhiều)
            $campaign->status = strtoupper($status);
            $campaign->effective_status = strtoupper($status);
            $campaign->save();

            Caching::clearCache(CacheKey::CACHE_DETAIL_META_CAMPAIGN, (string) $campaignId);

            return ServiceReturn::success(data: $apiResult->getData());
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'MetaService@updateCampaignStatus error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật giới hạn chi tiêu (spend_cap) chiến dịch Meta.
     * @param string $serviceUserId
     * @param string $campaignId
     * @param float $amount
     * @return ServiceReturn
     */
    public function updateCampaignSpendCap(string $serviceUserId, string $campaignId, float $amount): ServiceReturn
    {
        $serviceUserResult = $this->validateServiceUser($serviceUserId);
        if ($serviceUserResult->isError()) {
            return $serviceUserResult;
        }
        /** @var ServiceUser $serviceUser */
        $serviceUser = $serviceUserResult->getData();

        try {
            $campaign = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();

            if (!$campaign) {
                return ServiceReturn::error(message: __('meta.error.campaign_not_found'));
            }

            $apiResult = $this->metaBusinessService->updateCampaignSpendCap($campaign->campaign_id, $amount);
            if ($apiResult->isError()) {
                return $apiResult;
            }

            Caching::clearCache(CacheKey::CACHE_DETAIL_META_CAMPAIGN, (string) $campaignId);

            return ServiceReturn::success(data: $apiResult->getData());
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'MetaService@updateCampaignSpendCap error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy thông tin chi tiết chiến dịch quảng cáo Meta
     * @param string $serviceUserId
     * @param string $campaignId
     * @return ServiceReturn
     */
    public function getCampaignDetail(string $serviceUserId, string $campaignId): ServiceReturn
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
            // Lấy thông tin chi tiết chiến dịch từ cache
            $data = Caching::getCache(
                key: CacheKey::CACHE_DETAIL_META_CAMPAIGN,
                uniqueKey: $campaignId,
            );
            if ($data) {
                return ServiceReturn::success(data: $data);
            }
            // Còn không có trong cache, thì lấy dữ liệu từ đầu

            // Lấy thông tin chi tiết chiến dịch từ database
            $campaign = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('meta.error.campaign_not_found'));
            }
            // Lấy thông tin chi tiết insights chiến dịch từ Meta Business (today)
            $insightsTodayResult = $this->metaBusinessService->getCampaignInsights($campaign->campaign_id, AdDatePresetValues::TODAY);
            $insightsToday = [];
            if ($insightsTodayResult->isError()) {
                Logging::error(
                    message: 'MetaService@getCampaignDetail failed to fetch insights today',
                    context: [
                        'campaign_id' => $campaign->campaign_id,
                        'error' => $insightsTodayResult->getMessage(),
                    ],
                );
            } else {
                $insightsToday = $insightsTodayResult->getData()['data'][0] ?? [];
            }

            // Lấy thông tin chi tiết insights chiến dịch từ Meta Business (last 7 days)
            $insightsTotalResult = $this->metaBusinessService->getCampaignInsights($campaign->campaign_id, AdDatePresetValues::LAST_7D);
            $insightsTotal = [];
            if ($insightsTotalResult->isError()) {
                Logging::error(
                    message: 'MetaService@getCampaignDetail failed to fetch insights last_7d',
                    context: [
                        'campaign_id' => $campaign->campaign_id,
                        'error' => $insightsTotalResult->getMessage(),
                    ],
                );
            } else {
                $insightsTotal = $insightsTotalResult->getData()['data'][0] ?? [];
            }

            $insightsMaximumResult = $this->metaBusinessService->getCampaignInsights($campaign->campaign_id, AdDatePresetValues::MAXIMUM);
            $insightsMaximum = [];
            if ($insightsMaximumResult->isError()) {
                Logging::error(
                    message: 'MetaService@getCampaignDetail failed to fetch insights maximum',
                    context: [
                        'campaign_id' => $campaign->campaign_id,
                        'error' => $insightsMaximumResult->getMessage(),
                    ],
                );
            } else {
                $insightsMaximum = $insightsMaximumResult->getData()['data'][0] ?? [];
            }
            // Tổng chuyển đổi hôm nay
            $totalConversionsToday = 0;
            foreach ($insightsToday['actions'] ?? [] as $action) {
                $totalConversionsToday += (int)($action['value'] ?? 0);
            }
            // Tổng chuyển đổi trong tổng thời gian
            $totalConversionsTotal = 0;
            foreach ($insightsTotal['actions'] ?? [] as $action) {
                $totalConversionsTotal += (int)($action['value'] ?? 0);
            }

            // tính toán hiệu quả
            // 1. Lấy CPC (Mặc định là 0 nếu không có)
            $cpc = (float) ($insightsMaximum['cpc'] ?? 0);
            // 2. Lấy CPM (Mặc định là 0 nếu không có)
            $cpm = (float) ($insightsMaximum['cpm'] ?? 0);
            // 3. Lấy ROAS (Cái này Meta trả về dạng mảng object, cần xử lý kỹ)
            // Cấu trúc Meta trả về: "purchase_roas": [{ "action_type": "omni_purchase", "value": "2.5" }]
            $roas = 0;
            if (isset($insightsMaximum['purchase_roas'])) {
                foreach ($insightsMaximum['purchase_roas'] as $roasItem) {
                    // Thường lấy 'omni_purchase' hoặc phần tử đầu tiên
                    $roas = (float) $roasItem['value'];
                    break;
                }
            }
            $data = [
                // Các thông tin cơ bản
                'id' => $campaign->id,
                'service_user_id' => $campaign->service_user_id,
                'meta_account_id' => $campaign->meta_account_id,
                'campaign_id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status, // Trạng thái chiến dịch
                'effective_status' => $campaign->effective_status,// Trạng thái hiệu lực chiến dịch
                'objective' => $campaign->objective, // Mục tiêu chiến dịch
                'daily_budget' => $campaign->daily_budget, // Giới hạn chi tiêu mỗi ngày
                'budget_remaining' => $campaign->budget_remaining, // Giới hạn chi tiêu còn lại
                'created_time' => $campaign->created_time,  // Thời gian tạo chiến dịch
                'start_time' => $campaign->start_time, // Thời gian bắt đầu chạy chiến dịch
                'stop_time' => $campaign->stop_time, // Thời gian dừng chạy chiến dịch
                'last_synced_at' => $campaign->last_synced_at, // Thời gian cuối cùng đồng bộ dữ liệu

                // Tổng chi tiêu hôm nay
                'today_spend' => $insightsToday['spend'] ?? 0,
                // Tổng chi tiêu trong tổng thời gian
                'total_spend' => $insightsTotal['spend'] ?? 0,

                'cpc_avg' => $cpc,
                'cpm_avg' => $cpm,
                'roas_avg' => $roas,

                // đo lường hiệu quả (7 ngày)
                'insight' => [
                    // Tổng chi tiêu
                    'spend' => [
                        'today' => $insightsToday['spend'] ?? 0,
                        'total' => $insightsTotal['spend'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $insightsTotal['spend'] ?? 0,
                            $insightsToday['spend'] ?? 0
                        ),
                    ],
                    // Tổng lượt hiển thị
                    'impressions' => [
                        'today' => $insightsToday['impressions'] ?? 0,
                        'total' => $insightsTotal['impressions'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $insightsTotal['impressions'] ?? 0,
                            $insightsToday['impressions'] ?? 0
                        ),
                    ],
                    // Tổng lượt click
                    'clicks' => [
                        'today' => $insightsToday['clicks'] ?? 0,
                        'total' => $insightsTotal['clicks'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $insightsTotal['clicks'] ?? 0,
                            $insightsToday['clicks'] ?? 0
                        ),
                    ],
                    // Tổng chi phí click (CPC)
                    'cpc' => [
                        'today' => $insightsToday['cpc'] ?? 0,
                        'total' => $insightsTotal['cpc'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $insightsTotal['cpc'] ?? 0,
                            $insightsToday['cpc'] ?? 0
                        ),
                    ],
                    // Tổng chi phí hiển thị (CPM)
                    'cpm' => [
                        'today' => $insightsToday['cpm'] ?? 0,
                        'total' => $insightsTotal['cpm'] ?? 0,
                        'percent_change' => Helper::calculatePercentageChange(
                            $insightsTotal['cpm'] ?? 0,
                            $insightsToday['cpm'] ?? 0
                        ),
                    ],
                    // Tổng chuyển đổi hành động
                    'actions' => [
                        'today' => $totalConversionsToday,
                        'total' => $totalConversionsTotal,
                        'percent_change' => Helper::calculatePercentageChange(
                            $totalConversionsTotal,
                            $totalConversionsToday
                        ),
                    ],
                ]

            ];

            // Lưu vào cache
            Caching::setCache(
                key: CacheKey::CACHE_DETAIL_META_CAMPAIGN,
                value: $data,
                uniqueKey: $campaignId,
                expire: 15 // 15 phút
            );


            return ServiceReturn::success(
                data: $data,
            );

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
        }
    }


    /**
     * Lấy thông tin chi tiết về hiệu suất quảng cáo Meta cho một chiến dịch cụ thể
     * @param string $serviceUserId
     * @param string $campaignId
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
            // Lấy thông tin chi tiết hiệu suất quảng cáo từ cache
            $data = Caching::getCache(
                key: CacheKey::CACHE_DETAIL_META_INSIGHT,
                uniqueKey: $campaignId . $datePreset,
            );
            if ($data) {
                return ServiceReturn::success(data: $data);
            }
            // Lấy thông tin chi tiết chiến dịch từ database
            $campaign = $this->metaAdsCampaignRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->where('id', $campaignId)
                ->first();
            if (!$campaign) {
                return ServiceReturn::error(message: __('meta.error.campaign_not_found'));
            }
            $insightsResult = $this->metaBusinessService->getCampaignDailyInsights(
                campaignId: $campaign->campaign_id,
                datePreset: $datePreset
            );
            if ($insightsResult->isError()){
                return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
            }
            $insights = $insightsResult->getData();

            // set cache
            Caching::setCache(
                key: CacheKey::CACHE_DETAIL_META_INSIGHT,
                value: $insights,
                uniqueKey: $campaignId . $datePreset,
                expire: 15 // 15 phút
            );
            return ServiceReturn::success(
                data: $insights,
            );
        }catch (\Exception $exception){
            return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
        }
    }


    /**
     * Đồng bộ tài khoản quảng cáo từ Meta Business
     * @param ServiceUser $serviceUser
     * @return ServiceReturn
     */
    public function syncMetaAccounts(ServiceUser $serviceUser): ServiceReturn
    {
        $serviceUserConfig = $serviceUser->config_account ?? [];

        $bmId = null;
        $childBmId = $serviceUserConfig['child_bm_id'] ?? null;

        if ($childBmId) {
            // Nếu có BM con được chọn, sử dụng BM con
            $bmId = $childBmId;
        } else {
            // Nếu không có BM con, sử dụng BM gốc
            if (isset($serviceUserConfig['accounts']) && is_array($serviceUserConfig['accounts']) && !empty($serviceUserConfig['accounts'])) {
                $firstAccount = $serviceUserConfig['accounts'][0];
                if (isset($firstAccount['bm_ids']) && is_array($firstAccount['bm_ids']) && !empty($firstAccount['bm_ids'])) {
                    $bmId = $firstAccount['bm_ids'][0];
                }
            } else {
                $bmId = $serviceUserConfig['bm_id'] ?? null;
            }
        }

        // Nếu không có bmId thì thoát
        if (!$bmId) {
            return ServiceReturn::error('Missing bm_id in service user config');
        }

        // Đồng bộ danh sách BM con trước
        if (!$childBmId) {
            $this->syncBusinessManagers($bmId);
        }

        // Đồng bộ danh sách tài khoản quảng cáo:
        // owned_ad_accounts
        $this->syncMetaAccountsFromEdge($serviceUser, $bmId, 'owner');
        // client_ad_accounts (được share vào BM)
        $this->syncMetaAccountsFromEdge($serviceUser, $bmId, 'client');
        return ServiceReturn::success();
    }

    /**
     * Đồng bộ tài khoản quảng cáo trực tiếp từ một Business Manager ID
     */
    public function syncFromBusinessManagerId(string $bmId): ServiceReturn
    {
        try {
            // Reset API để đảm bảo sử dụng config mới nhất khi sync
            $this->metaBusinessService->resetApi();

            // Đồng bộ BM con trước
            $this->syncBusinessManagers($bmId);

            // Đồng bộ owned_ad_accounts
            $this->syncMetaAccountsFromManagerEdge($bmId, 'owner');
            // Đồng bộ client_ad_accounts (được share vào BM)
            $this->syncMetaAccountsFromManagerEdge($bmId, 'client');

            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(
                message: 'MetaService@syncFromBusinessManagerId error: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Đồng bộ danh sách BM con từ một BM gốc
     */
    private function syncBusinessManagers(string $parentBmId): void
    {
        //Lấy thông tin BM gốc
        // Fields hợp lệ: id, name, verification_status, primary_page
        $parentInfo = $this->metaBusinessService->getBusinessById($parentBmId);

        if ($parentInfo->isSuccess()) {
            $data = $parentInfo->getData();
            $primaryPage = $data['primary_page'] ?? null;

            $this->metaBusinessManagerRepository->updateOrCreate(
                ['bm_id' => $parentBmId],
                [
                    'parent_bm_id' => null,
                    'name' => $data['name'] ?? 'N/A',
                    'primary_page_id' => $primaryPage['id'] ?? null,
                    'primary_page_name' => $primaryPage['name'] ?? null,
                    'verification_status' => $data['verification_status'] ?? null,
                    'is_primary' => true,
                    'last_synced_at' => now(),
                ]
            );
        } else {
            Logging::error('MetaService@syncBusinessManagers: cannot fetch parent BM info: ' . $parentInfo->getMessage());
        }

        // Đồng bộ BM con:
        // - owned_businesses  -> type = 'owned'
        // - clients (client BMs) -> type = 'client' để đi vào nhánh getClientBusinessesPaginated()
        // - agencies (agency/partner BMs) -> type = 'agency' để đi vào nhánh getAgencyBusinessesPaginated()
        $this->syncBusinessManagersFromEdge($parentBmId, 'owned');
        $this->syncBusinessManagersFromEdge($parentBmId, 'client');
        $this->syncBusinessManagersFromEdge($parentBmId, 'agency');
    }

    /**
     * Đồng bộ BM con từ một edge cụ thể
     */
    private function syncBusinessManagersFromEdge(string $parentBmId, string $type = 'owned'): void
    {
        $after = null;
        do {
            $result = match ($type) {
                'client' => $this->metaBusinessService->getClientBusinessesPaginated(
                    bmId: $parentBmId,
                    limit: 100,
                    after: $after
                ),
                'agency' => $this->metaBusinessService->getAgencyBusinessesPaginated(
                    bmId: $parentBmId,
                    limit: 100,
                    after: $after
                ),
                default => $this->metaBusinessService->getOwnedBusinessesPaginated(
                    bmId: $parentBmId,
                    limit: 100,
                    after: $after
                ),
            };

            if ($result->isError()) {
                Logging::error('Error sync business managers from ' . $type . ' edge: ' . $result->getMessage(), [
                    'bm_id' => $parentBmId,
                    'edge' => $type,
                ]);
                return;
            }

            $data = $result->getData();
            $businesses = $data['data'] ?? [];

            foreach ($businesses as $businessData) {
                try {
                    $primaryPage = $businessData['primary_page'] ?? null;
                    $this->metaBusinessManagerRepository->updateOrCreate(
                        [
                            'bm_id' => $businessData['id'],
                        ],
                        [
                            'parent_bm_id' => $parentBmId,
                            'name' => $businessData['name'] ?? null,
                            'primary_page_id' => $primaryPage['id'] ?? null,
                            'primary_page_name' => $primaryPage['name'] ?? null,
                            'verification_status' => $businessData['verification_status'] ?? null,
                            'timezone_id' => $businessData['timezone_id'] ?? null,
                            'currency' => $businessData['currency'] ?? null,
                            'is_primary' => false,
                            'last_synced_at' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    Logging::error('Error sync business manager: ' . $e->getMessage());
                }
            }

            $after = $data['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    /**
     * Đồng bộ tài khoản quảng cáo từ một edge cụ thể
     */
    private function syncMetaAccountsFromManagerEdge(string $bmId, string $type = 'owner'): void
    {
        $after = null;
        do {
            $result = $type === 'client'
                ? $this->metaBusinessService->getClientAdsAccountPaginated(
                    bmId: $bmId,
                    limit: 100,
                    after: $after
                )
                : $this->metaBusinessService->getOwnerAdsAccountPaginated(
                    bmId: $bmId,
                    limit: 100,
                    after: $after
                );

            if ($result->isError()) {
                Logging::error('Error sync ads account from manager edge ' . $type . ': ' . $result->getMessage());
                return;
            }

            $data = $result->getData();
            $accounts = $data['data'] ?? [];

            foreach ($accounts as $adsAccountData) {
                $detailResponse = $this->metaBusinessService->getDetailAdsAccount($adsAccountData['id']);
                if ($detailResponse->isError()) {
                    continue;
                }
                $detail = $detailResponse->getData();

                try {
                    // Tìm account hiện tại để kiểm tra service_user_id
                    $existingAccount = $this->metaAccountRepository->query()
                        ->where('account_id', $detail['id'])
                        ->first();

                    // Chỉ set service_user_id = null nếu account chưa được gán cho service_user nào
                    // Nếu đã có service_user_id, giữ nguyên để không ghi đè dữ liệu của user
                    $updateData = [
                        'account_name' => $detail['name'],
                        'account_status' => $detail['account_status'],
                        'disable_reason' => $detail['disable_reason'] ?? null,
                        'spend_cap' => $detail['spend_cap'],
                        'amount_spent' => $detail['amount_spent'],
                        'balance' => $detail['balance'],
                        'currency' => $detail['currency'],
                        'created_time' => $detail['created_time'] ? Carbon::parse($detail['created_time']) : null,
                        'is_prepay_account' => (bool)$detail['is_prepay_account'],
                        'timezone_id' => $detail['timezone_id'],
                        'timezone_name' => $detail['timezone_name'],
                        'last_synced_at' => now(),
                    ];

                    // Chỉ set service_user_id = null nếu account chưa có service_user_id
                    if (!$existingAccount || !$existingAccount->service_user_id) {
                        $updateData['service_user_id'] = null;
                    }
                    $updateData['business_manager_id'] = $bmId;

                    $this->metaAccountRepository->query()->updateOrCreate(
                        [
                            'account_id' => $detail['id'],
                        ],
                        $updateData
                    );
                } catch (\Exception $e) {
                    Logging::error('Error sync manager ads account: ' . $e->getMessage());
                }
            }

            $after = $data['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    /**
     * Đồng bộ tài khoản quảng cáo từ một edge cụ thể (owned hoặc client)
     */
    private function syncMetaAccountsFromEdge(ServiceUser $serviceUser, string $bmId, string $type = 'owner'): void
    {
        $after = null;
        do {
            $result = $type === 'client'
                ? $this->metaBusinessService->getClientAdsAccountPaginated(
                    bmId: $bmId,
                    limit: 100,
                    after: $after
                )
                : $this->metaBusinessService->getOwnerAdsAccountPaginated(
                    bmId: $bmId,
                    limit: 100,
                    after: $after
                );

            if ($result->isError()) {
                // log lỗi nhưng không dừng hẳn luồng cho edge còn lại
                Logging::error('Error sync ads account from ' . $type . ' edge: ' . $result->getMessage());
                return;
            }

            $data = $result->getData();
            $accounts = $data['data'] ?? [];

            foreach ($accounts as $adsAccountData) {
                $detailResponse = $this->metaBusinessService->getDetailAdsAccount($adsAccountData['id']);
                if ($detailResponse->isError()) {
                    continue;
                }
                $detail = $detailResponse->getData();

                try {
                    $this->metaAccountRepository->query()->updateOrCreate(
                        [
                            'account_id' => $detail['id'],
                        ],
                        [
                            'service_user_id' => $serviceUser->id,
                            'business_manager_id' => $bmId,
                            'account_name' => $detail['name'],
                            'account_status' => $detail['account_status'],
                            'disable_reason' => $detail['disable_reason'] ?? null,
                            'spend_cap' => $detail['spend_cap'],
                            'amount_spent' => $detail['amount_spent'],
                            'balance' => $detail['balance'],
                            'currency' => $detail['currency'],
                            'created_time' => $detail['created_time'] ? Carbon::parse($detail['created_time']) : null,
                            'is_prepay_account' => (bool)$detail['is_prepay_account'],
                            'timezone_id' => $detail['timezone_id'],
                            'timezone_name' => $detail['timezone_name'],
                            'last_synced_at' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    Logging::error('Error sync ads account: ' . $e->getMessage());
                }
            }

            $after = $data['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    /**
     * Đồng bộ chiến dịch quảng cáo từ Meta Business
     * @param ServiceUser $serviceUser
     * @return ServiceReturn
     */
    public function syncMetaAdsAndCampaigns(ServiceUser $serviceUser): ServiceReturn
    {
        try {
            $this->metaAccountRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->chunkById(50, function (Collection $metaAccounts) use ($serviceUser) {
                    foreach ($metaAccounts as $metaAccount) {
                        // sync insight của ads account
                        $insightResult = $this->metaBusinessService->getAccountDailyInsights(
                            accountId: $metaAccount->account_id,
                        );
                        // Xử lý kết quả
                        // Nếu bị lỗi thì next sang tài khoản tiếp theo
                        if ($insightResult->isSuccess()) {
                            $insights = $insightResult->getData()['data'] ?? [];
                            foreach ($insights as $insight) {
                                // Lấy ROAS (Cái này Meta trả về dạng mảng object, cần xử lý kỹ)
                                // Cấu trúc Meta trả về: "purchase_roas": [{ "action_type": "omni_purchase", "value": "2.5" }]
                                $roas = $this->getRoas($insight);
                                // Lưu dữ liệu vào DB
                                // try catch lồng nhau để tránh lỗi khi lưu vào DB và ko bị ảnh hưởng đến các vòng lặp khác
                                try {
                                    $this->metaAdsAccountInsightRepository->query()->updateOrCreate(
                                        [
                                            'service_user_id' => $serviceUser->id,
                                            'meta_account_id' => $metaAccount->id,
                                            'date' => $insight['date_start'], // Lưu ngày bắt đầu của insight
                                        ],
                                        [
                                            'spend' => $insight['spend'] ?? null,
                                            'impressions' => $insight['impressions'] ?? null,
                                            'reach' => $insight['reach'] ?? null,
                                            'frequency' => $insight['frequency'] ?? null,
                                            'clicks' => $insight['clicks'] ?? null,
                                            'inline_link_clicks' => $insight['inline_link_clicks'] ?? null,
                                            'ctr' => $insight['ctr'] ?? null,
                                            'cpc' => $insight['cpc'] ?? null,
                                            'cpm' => $insight['cpm'] ?? null,
                                            'actions' => $insight['actions'] ?? null,
                                            'purchase_roas' => $roas ?? null,
                                            'last_synced_at' => now(),
                                        ]
                                    );
                                }
                                catch (\Exception $exception){
                                    Logging::error('Error sync ads account insight: ' . $exception->getMessage());
                                }
                            }
                        }else{
                            Logging::error('Error sync ads account insight: ' . $insightResult->getMessage());
                        }


                        // sync chiến dịch quảng cáo
                        $after = null;
                        $campaignResult = $this->metaBusinessService->getCampaignsPaginated(
                            accountId: $metaAccount->account_id,
                            limit: 100,
                            after: $after
                        );
                        // Xử lý kết quả
                        // Nếu bị lỗi thì next sang tài khoản tiếp theo
                        if ($campaignResult->isSuccess()) {
                            $data = $campaignResult->getData();
                            $campaigns = $data['data'] ?? [];
                            foreach ($campaigns as $campaignData) {
                                // try catch lồng nhau để tránh lỗi khi lưu vào DB và ko bị ảnh hưởng đến các vòng lặp khác
                                try {
                                    // Lưu dữ liệu vào DB
                                    $this->metaAdsCampaignRepository->query()->updateOrCreate(
                                        [
                                            'campaign_id' => $campaignData['id'],
                                            'service_user_id' => $serviceUser->id,
                                            'meta_account_id' => $metaAccount->id,
                                        ],
                                        [
                                            'name' => $campaignData['name'],
                                            'status' => $campaignData['status'],
                                            'effective_status' => $campaignData['effective_status'],
                                            'objective' => $campaignData['objective'],
                                            'daily_budget' => $campaignData['daily_budget'] ?? null,
                                            'budget_remaining' => $campaignData['budget_remaining'] ?? null,
                                            'created_time' => ($campaignData['created_time'] ?? null) ? Carbon::parse($campaignData['created_time']) : null,
                                            'start_time' => ($campaignData['start_time'] ?? null) ? Carbon::parse($campaignData['start_time']) : null,
                                            'stop_time' => ($campaignData['stop_time'] ?? null) ? Carbon::parse($campaignData['stop_time']) : null,
                                            'last_synced_at' => now(),
                                        ]
                                    );
                                }
                                catch (\Exception $e) {
                                    Logging::error('Error sync ads campaign: ' . $e->getMessage());
                                }
                            }
                        }else{
                            Logging::error('Error sync ads campaign: ' . $campaignResult->getMessage());
                        }

                    }
                });
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            return ServiceReturn::error('Error sync ads campaign: ' . $exception->getMessage());
        }
    }

    /**
     * Lấy ROAS (Return On Ad Spend) từ insights.
     * @param $insights
     * @return float
     */
    private function getRoas($insights)
    {
        $roas = 0.0;
        if (isset($insights['purchase_roas'])) {
            foreach ($insights['purchase_roas'] as $roasItem) {
                // Thường lấy 'omni_purchase' hoặc phần tử đầu tiên
                $roas = (float) $roasItem['value'];
                break;
            }
        }
        return $roas;
    }

    // Lấy insights tổng hợp từ database
    protected function getAccountsInsightsSummaryFromDatabase(array $metaAccountIds, string $datePreset = 'maximum'): ServiceReturn
    {
        try {
            if (empty($metaAccountIds)) {
                return ServiceReturn::success(data: [
                    'spend' => 0.0,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'roas' => 0.0,
                ]);
            }

            $query = $this->metaAdsAccountInsightRepository->query()
                ->whereIn('meta_account_id', $metaAccountIds);

            // Convert date preset thành date range
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

            // Aggregate metrics
            $totalSpend = 0.0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalConversions = 0;
            $totalRoas = 0.0;
            $roasCount = 0;

            foreach ($insights as $insight) {
                // Sum spend
                $totalSpend += (float) ($insight->spend ?? 0);

                // Sum impressions
                $totalImpressions += (int) ($insight->impressions ?? 0);

                // Sum clicks
                $totalClicks += (int) ($insight->clicks ?? 0);

                // Tính conversions từ actions (JSON)
                if ($insight->actions && is_array($insight->actions)) {
                    foreach ($insight->actions as $action) {
                        if (isset($action['value'])) {
                            $totalConversions += (int) $action['value'];
                        }
                    }
                }

                // Tính ROAS từ purchase_roas
                if ($insight->purchase_roas) {
                    $roasValue = (float) $insight->purchase_roas;
                    if ($roasValue > 0) {
                        $totalRoas += $roasValue;
                        $roasCount++;
                    }
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
        } catch (\Exception $exception) {
            Logging::error(
                message: "Error get accounts insights summary from database: " . $exception->getMessage(),
                exception: $exception,
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
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
     * Lấy bảng xếp hạng chi tiêu các tài khoản Meta Ads
     */
    public function getAccountSpendingRanking(?Carbon $startDate = null, ?Carbon $endDate = null): ServiceReturn
    {
        try {
            // Query insights từ database, group by account và sum spend
            $query = $this->metaAdsAccountInsightRepository->query()
                ->select('meta_account_id', DB::raw('SUM(spend::numeric) as total_spend'))
                ->groupBy('meta_account_id');

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
            $accountIds = $insights->pluck('meta_account_id')->toArray();
            if (empty($accountIds)) {
                return ServiceReturn::success(data: []);
            }

            $accounts = $this->metaAccountRepository->query()
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id');

            // Tạo ranking list
            $ranking = [];
            foreach ($insights as $insight) {
                $account = $accounts->get($insight->meta_account_id);
                if (!$account) {
                    continue;
                }

                $statusEnum = MetaAdsAccountStatus::tryFrom((int) $account->account_status);
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
        } catch (\Exception $exception) {
            Logging::error(
                message: "Error get account spending ranking: " . $exception->getMessage(),
                exception: $exception,
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy dữ liệu report cho agency và customer
     * @return ServiceReturn
     */
    public function getReportData(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }
            // Chỉ cho phép agency và customer
            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // 1. Service Users (của user này)
            $metaServiceUsers = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->with(['package', 'metaAccount'])
                ->whereHas('package', function ($query) {
                    $query->where('platform', PlatformType::META->value);
                })
                ->get();

            $metaAccounts = $this->metaAccountRepository->query()
                ->whereIn('service_user_id', $metaServiceUsers->pluck('id'))
                ->get();
            $metaAccountIds = $metaAccounts->pluck('id')->toArray();
            // 2. Lấy dữ liệu tổng thể
            $totalResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'maximum');
            if ($totalResult->isError()) {
                return ServiceReturn::error(message: __('common_error.server_error'));
            }
            // 3. Lấy dữ liệu hôm nay
            $todayResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'today');
            if ($todayResult->isError()) {
                return ServiceReturn::error(message: __('common_error.server_error'));
            }
            // Lấy spend của toàn account
            $accountSpend = $metaAccounts->map(function ($account) {
                $amountSpend = $account->amount_spent;
                return [
                    'account_id' => (string) ($account->account_id ?? $account->id),
                    'account_name' => $account->account_name
                        ?? $account->name
                        ?? (string) ($account->account_id ?? $account->id),
                    'account_name' => $account->account_name,
                    'amount_spent' => $amountSpend,
                ];
            })->values()->toArray();
            return ServiceReturn::success(data: [
                'total_spend' => $totalResult->getData()['spend'],
                'today_spend' => $todayResult->getData()['spend'],
                'account_spend' => $accountSpend,
            ]);

        }
        catch (\Exception $exception) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }


    public function getReportInsights(string $datePreset): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }
            // Chỉ cho phép agency và customer
            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }
            // 1. Service Users (của user này)
            $serviceUserIds = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->whereHas('package', function ($query) {
                    $query->where('platform', PlatformType::META->value);
                })
                ->pluck('id') // Chỉ lấy cột ID
                ->toArray();

            // Check nhanh: Nếu user không có gói dịch vụ nào thì trả về rỗng luôn
            if (empty($serviceUserIds)) {
                return ServiceReturn::success(data: [
                    'total_spend_period' => 0,
                    'chart' => []
                ]);
            }
            // 2. Tính toán khoảng thời gian (StartDate - EndDate)
            $endDate = Carbon::today();
            $startDate = match ($datePreset) {
                AdDatePresetValues::LAST_7D => Carbon::today()->subDays(6),
                AdDatePresetValues::LAST_14D => Carbon::today()->subDays(13),
                AdDatePresetValues::LAST_28D => Carbon::today()->subDays(27),
                AdDatePresetValues::LAST_30D => Carbon::today()->subDays(29),
                AdDatePresetValues::LAST_90D => Carbon::today()->subDays(89),
                default => Carbon::today(),
            };
            $records = $this->metaAdsAccountInsightRepository->query()
                ->whereIn('service_user_id', $serviceUserIds)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->groupBy('date') // Group theo ngày (nó sẽ gộp spend của tất cả user trong ngày đó lại)
                ->orderBy('date', 'ASC')
                ->get([
                    'date',
                    DB::raw('SUM(spend::numeric) as total_spend')
                ])
                ->keyBy('date');

            $chartData = [];
            foreach ($records as $record) {
                $record->total_spend = (float)$record->total_spend;
                $chartData[] = [
                    'value' => (float)$record->total_spend,
                    'date' => $record->date->format('Y-m-d'),
                ];
            }
            return ServiceReturn::success(data: [
                'total_spend_period' => collect($chartData)->sum('value'),
                'chart' => $chartData
            ]);
        }catch (\Exception $exception) {
            Logging::error(
                message: "Error get report insights from database: " . $exception->getMessage(),
                exception: $exception,
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy dữ liệu dashboard cho agency và customer
    public function getDashboardData(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ cho phép agency và customer
            if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // 1. Service Users (của user này)
            $serviceUsers = $this->serviceUserRepository->query()
                ->where('user_id', $user->id)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->with(['package', 'metaAccount'])
                ->get();

            $metaServiceUsers = $serviceUsers->filter(function ($serviceUser) {
                return $serviceUser->package->platform === PlatformType::META->value;
            });

            $metaAccounts = $this->metaAccountRepository->query()
                ->whereIn('service_user_id', $metaServiceUsers->pluck('id'))
                ->get();

            $totalAccounts = $metaAccounts->count();
            $activeAccounts = $metaAccounts->where('account_status', 1)->count();
            $pausedAccounts = $totalAccounts - $activeAccounts;

            // 3. Lấy insights từ DATABASE
            $metaAccountIds = $metaAccounts->pluck('id')->toArray();

            if (empty($metaAccountIds)) {
                // Nếu không có account, trả về dữ liệu rỗng
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
            } else {
                // Lấy tổng spend (maximum) - tổng từ đầu đến giờ
                $totalResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'maximum');
                $totalSpend = 0.0;
                $totalImpressions = 0;
                $totalClicks = 0;
                $totalConversions = 0;
                $avgRoas = 0.0;

                if ($totalResult->isSuccess()) {
                    $totalData = $totalResult->getData();
                    $totalSpend = (float) ($totalData['spend'] ?? 0);
                    $totalImpressions = (int) ($totalData['impressions'] ?? 0);
                    $totalClicks = (int) ($totalData['clicks'] ?? 0);
                    $totalConversions = (int) ($totalData['conversions'] ?? 0);
                    $avgRoas = (float) ($totalData['roas'] ?? 0);
                }

                // Lấy spend hôm nay
                $todayResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'today');
                $todaySpend = 0.0;

                if ($todayResult->isSuccess()) {
                    $todayData = $todayResult->getData();
                    $todaySpend = (float) ($todayData['spend'] ?? 0);
                }

                // Lấy dữ liệu hôm qua để tính percent change cho "Chi tiêu hôm nay"
                $yesterdayResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'yesterday');
                $yesterdaySpend = 0.0;

                if ($yesterdayResult->isSuccess()) {
                    $yesterdayData = $yesterdayResult->getData();
                    $yesterdaySpend = (float) ($yesterdayData['spend'] ?? 0);
                }

                // Lấy dữ liệu last_30d để tính percent change cho "Tổng chỉ tiêu"
                // So sánh last_30d vs previous 30 days (last_90d - last_30d)
                $last30dResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'last_30d');
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

                // Lấy dữ liệu last_90d để tính previous 30 days (last_90d - last_30d)
                $last90dResult = $this->getAccountsInsightsSummaryFromDatabase($metaAccountIds, 'last_90d');
                $previous30dSpend = 0.0;
                $previous30dImpressions = 0;
                $previous30dClicks = 0;
                $previous30dConversions = 0;

                if ($last90dResult->isSuccess()) {
                    $last90dData = $last90dResult->getData();
                    // Previous 30 days = last_90d - last_30d
                    $previous30dSpend = (float) ($last90dData['spend'] ?? 0) - $last30dSpend;
                    $previous30dImpressions = (int) ($last90dData['impressions'] ?? 0) - $last30dImpressions;
                    $previous30dClicks = (int) ($last90dData['clicks'] ?? 0) - $last30dClicks;
                    $previous30dConversions = (int) ($last90dData['conversions'] ?? 0) - $last30dConversions;
                }

                // 4. Tính percent change thực tế
                // "Tổng chỉ tiêu": So sánh last_30d vs previous 30 days
                $spendPercentChange = Helper::calculatePercentageChange($previous30dSpend, $last30dSpend);
                $impressionsPercentChange = Helper::calculatePercentageChange($previous30dImpressions, $last30dImpressions);
                $clicksPercentChange = Helper::calculatePercentageChange($previous30dClicks, $last30dClicks);
                $conversionsPercentChange = Helper::calculatePercentageChange($previous30dConversions, $last30dConversions);

                // "Chi tiêu hôm nay": So sánh ngày hôm qua với hôm nay
                $todaySpendPercentChange = Helper::calculatePercentageChange($yesterdaySpend, $todaySpend);
            }

            // 5. Tính metrics
            $avgCpc = $totalClicks > 0 ? ($totalSpend / $totalClicks) : 0.0;
            $avgCpm = $totalImpressions > 0 ? (($totalSpend / $totalImpressions) * 1000) : 0.0;
            $conversionRate = $totalClicks > 0 ? (($totalConversions / $totalClicks) * 100) : 0.0;

            // 6. Ngân sách: với trả sau thì không có top-up upfront, chỉ hiển thị chi tiêu
            $hasPostpay = $serviceUsers->contains(function ($serviceUser) {
                $config = $serviceUser->config_account ?? [];
                return ($config['payment_type'] ?? '') === 'postpay';
            });
            $totalBudget = (float) $serviceUsers->sum('budget') ?? 0.0;
            if ($hasPostpay) {
                // Postpay: không có tổng ngân sách upfront; hiển thị chi tiêu hôm nay, remaining = 0
                $totalBudget = 0.0;
                $budgetRemaining = 0.0;
                $budgetUsagePercent = 0.0;
            } else {
                $budgetRemaining = max(0, $totalBudget - $todaySpend);
                $budgetUsagePercent = $totalBudget > 0 ? (($todaySpend / $totalBudget) * 100) : 0.0;
            }
            $budgetUsed = $todaySpend;

            // 7. Cảnh báo lỗi
            $criticalErrors = 0;
            $accountsWithErrors = 0;
            foreach ($metaAccounts as $account) {
                if ($account->account_status != 1) {
                    $accountsWithErrors++;
                    $criticalErrors++;
                }
            }

            // 8. số dư ví
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
                    'critical_alerts' => $criticalErrors,
                    'accounts_with_errors' => $accountsWithErrors,
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
                    'critical_errors' => $criticalErrors,
                    'accounts_with_errors' => $accountsWithErrors,
                ],
            ];

            return ServiceReturn::success(data: $data);
        } catch (\Exception $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Format số với K, M suffix
     */
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

    /**
     * Kiểm tra và tự động tạm dừng tài khoản nếu spending > balance + threshold
     * @param float $threshold Ngưỡng cảnh báo (mặc định 100 USD)
     * @return ServiceReturn
     */
    public function checkAndAutoPauseAccounts(float $threshold = 100.0): ServiceReturn
    {
        try {
            $accounts = $this->metaAccountRepository->query()
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
                        $campaigns = $this->metaAdsCampaignRepository->query()
                            ->where('meta_account_id', $account->id)
                            ->where('status', '!=', 'PAUSED')
                            ->where('status', '!=', 'DELETED')
                            ->get();

                        foreach ($campaigns as $campaign) {
                            if ($account->serviceUser) {
                                $pauseResult = $this->updateCampaignStatus(
                                    (string) $account->serviceUser->id,
                                    (string) $campaign->id,
                                    'PAUSED'
                                );
                                if ($pauseResult->isError()) {
                                    Logging::web('MetaService@checkAndAutoPauseAccounts: Failed to pause campaign', [
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
                        $notificationResult = $this->metaAdsNotificationService->sendLowBalanceAlert(
                            $account,
                            $threshold
                        );
                        if ($notificationResult->isSuccess()) {
                            $notified++;
                        }

                        Logging::web('MetaService@checkAndAutoPauseAccounts: Auto-paused account (low balance)', [
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
                    // Meta Ads API hỗ trợ date_preset: "maximum" để lấy lifetime spending
                    // amount_spent là chi tiêu tích lũy (lifetime) từ Meta API, ưu tiên dùng
                    $lifetimeSpending = (float) ($account->amount_spent ?? 0);

                    // Nếu không có amount_spent, lấy từ insights database (maximum = tất cả insights đã sync)
                    if ($lifetimeSpending == 0) {
                        $insightsResult = $this->getAccountsInsightsSummaryFromDatabase(
                            [(string) $account->id],
                            'maximum' // Lấy tất cả insights từ database (không filter date)
                        );
                        if ($insightsResult->isError()) {
                            $errors++;
                            continue;
                        }
                        $lifetimeSpending = (float) ($insightsResult->getData()['spend'] ?? 0);
                    }

                    $thresholdAmount = $balance + $threshold;

                    // Kiểm tra nếu chi tiêu tích lũy vượt quá số dư + ngưỡng an toàn
                    if ($lifetimeSpending > $thresholdAmount) {
                        // Pause tất cả campaigns trong account
                        $campaigns = $this->metaAdsCampaignRepository->query()
                            ->where('meta_account_id', $account->id)
                            ->where('status', '!=', 'PAUSED')
                            ->where('status', '!=', 'DELETED')
                            ->get();

                        foreach ($campaigns as $campaign) {
                            if ($account->serviceUser) {
                                $pauseResult = $this->updateCampaignStatus(
                                    (string) $account->serviceUser->id,
                                    (string) $campaign->id,
                                    'PAUSED'
                                );
                                if ($pauseResult->isError()) {
                                    Logging::web('MetaService@checkAndAutoPauseAccounts: Failed to pause campaign', [
                                        'account_id' => $account->id,
                                        'campaign_id' => $campaign->id,
                                        'error' => $pauseResult->getMessage(),
                                    ]);
                                }
                            }
                        }

                        $paused++;

                        // Gửi thông báo
                        $notificationResult = $this->metaAdsNotificationService->sendSpendingExceededAlert(
                            $account,
                            $lifetimeSpending,
                            $threshold
                        );
                        if ($notificationResult->isSuccess()) {
                            $notified++;
                        }

                        Logging::web('MetaService@checkAndAutoPauseAccounts: Auto-paused account (spending exceeded)', [
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
                        message: 'MetaService@checkAndAutoPauseAccounts: Error processing account',
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
                message: 'MetaService@checkAndAutoPauseAccounts: Unexpected error',
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
