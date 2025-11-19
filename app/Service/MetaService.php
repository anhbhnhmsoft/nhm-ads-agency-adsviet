<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Repositories\MetaAccountRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\ServiceUserRepository;
use Carbon\Carbon;
use FacebookAds\Object\Values\AdDatePresetValues;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $e) {
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
            // BƯỚC 1: GỌI 2 API INSIGHTS
            // -----------------------------------------------------------------
            // Lần 1: Lấy TỔNG CHI TIÊU
            $totalResult = $this->metaBusinessService->getAccountInsightsByCampaign(
                accountId: $adsAccount->account_id,
                datePreset: 'maximum',
                fields: ['campaign_id', 'spend']
            );
            if ($totalResult->isError()) {
                return ServiceReturn::error(__('meta.error.failed_to_fetch_campaigns'));
            }
            // Lần 2: Lấy CHI TIÊU HÔM NAY (Mới)
            $todayResult = $this->metaBusinessService->getAccountInsightsByCampaign(
                accountId: $adsAccount->account_id,
                datePreset: 'today',
                fields: ['campaign_id', 'spend']
            );
            if ($todayResult->isError()) {
                return ServiceReturn::error(__('meta.error.failed_to_fetch_campaigns'));
            }

            $totalData = $totalResult->getData();
            $todayData = $todayResult->getData();
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
            if ($insightsTodayResult->isError()) {
                return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
            }
            // Lấy thông tin chi tiết insights chiến dịch từ Meta Business (last 7 days)
            $insightsTotalResult = $this->metaBusinessService->getCampaignInsights($campaign->campaign_id, AdDatePresetValues::LAST_7D);
            if ($insightsTotalResult->isError()) {
                return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
            }

            $insightsMaximumResult = $this->metaBusinessService->getCampaignInsights($campaign->campaign_id, AdDatePresetValues::MAXIMUM);
            if ($insightsMaximumResult->isError()) {
                return ServiceReturn::error(message: __('meta.error.failed_to_fetch_campaign_detail'));
            }


            $insightsToday = $insightsTodayResult->getData()['data'][0] ?? [];
            $insightsTotal = $insightsTotalResult->getData()['data'][0] ?? [];
            $insightsMaximum = $insightsMaximumResult->getData()['data'][0] ?? [];
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
        $serviceUserConfig = $serviceUser->config_account;
        $bmId = $serviceUserConfig['bm_id'];
        // Nếu không có bmId thì thoát
        if (!$bmId) {
            return ServiceReturn::error('Missing bm_id in service user config');
        }
        // Đồng bộ danh sách tài khoản quảng cáo
        $after = null;
        do {
            // 1. Gọi API (dùng hàm phân trang của bạn)
            $result = $this->metaBusinessService->getOwnerAdsAccountPaginated(
                bmId: $bmId,
                limit: 100,
                after: $after
            );
            // 2. Xử lý kết quả
            // Nếu bị lỗi thì thoát
            if ($result->isError()) {
                // Xử lý lỗi sau
                return ServiceReturn::error('Error sync ads account: ' . $result->getMessage());
            }
            $data = $result->getData();
            $accounts = $data['data'] ?? [];
            foreach ($accounts as $adsAccountData) {
                // Lấy chi tiết tài khoản quảng cáo
                $detailResponse = $this->metaBusinessService->getDetailAdsAccount($adsAccountData['id']);
                // Xử lý lỗi sau
                if ($detailResponse->isError()) {
                    continue;
                }
                $detail = $detailResponse->getData();
                try {
                    // Lưu dữ liệu vào DB
                    $this->metaAccountRepository->query()->updateOrCreate(
                        [
                            'account_id' => $detail['id'],
                            'service_user_id' => $serviceUser->id,
                        ],
                        [
                            'account_name' => $detail['name'],
                            'account_status' => $detail['account_status'],
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
            // Lấy con trỏ phân trang
            $after = $data['paging']['cursors']['after'] ?? null;
        } while ($after);
        return ServiceReturn::success();
    }

    /**
     * Đồng bộ chiến dịch quảng cáo từ Meta Business
     * @param ServiceUser $serviceUser
     * @return ServiceReturn
     */
    public function syncMetaAdsCampaigns(ServiceUser $serviceUser): ServiceReturn
    {
        try {
            $this->metaAccountRepository->query()
                ->where('service_user_id', $serviceUser->id)
                ->chunkById(50, function (Collection $metaAccounts) use ($serviceUser) {
                    foreach ($metaAccounts as $metaAccount) {
                        $after = null;
                        $campaignResult = $this->metaBusinessService->getCampaignsPaginated(
                            accountId: $metaAccount->account_id,
                            limit: 100,
                            after: $after
                        );
                        // Xử lý kết quả
                        // Nếu bị lỗi thì next sang tài khoản tiếp theo
                        if ($campaignResult->isError()) {
                            // Xử lý lỗi sau
                            Logging::error('Error sync ads campaign: ' . $campaignResult->getMessage());
                            continue;
                        }
                        $data = $campaignResult->getData();
                        $campaigns = $data['data'] ?? [];
                        foreach ($campaigns as $campaignData) {
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
                            } catch (\Exception $e) {
                                Logging::error('Error sync ads campaign: ' . $e->getMessage());
                            }
                        }
                    }
                });
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            return ServiceReturn::error('Error sync ads campaign: ' . $exception->getMessage());
        }
    }
}
