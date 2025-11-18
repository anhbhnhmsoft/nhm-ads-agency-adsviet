<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Repositories\MetaAccountRepository;
use App\Repositories\MetaAdsCampaignRepository;
use App\Repositories\ServiceUserRepository;
use Carbon\Carbon;
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
            $query = $this->metaAdsCampaignRepository->query();
            $query = $this->metaAdsCampaignRepository->filterQuery(
                $query,
                [
                    'service_user_id' => $serviceUser->id,
                    'meta_account_id' => $accountId,
                ]);
            $query = $this->metaAdsCampaignRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách chiến dịch quảng cáo MetaService@getCampaigns: ' . $e->getMessage(),
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
