<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Resources\GoogleAdsAccountResource;
use App\Http\Resources\GoogleAdsCampaignResource;
use App\Service\GoogleAdsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleAdsController extends Controller
{
    public function __construct(
        protected GoogleAdsService $googleAdsService,
    ) {
    }

    /**
     * Lấy danh sách tài khoản quảng cáo theo service user id
     * @param string $serviceUserId
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdsAccount(string $serviceUserId, Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->googleAdsService->getAdsAccountPaginatedByServiceUserId(
            serviceUserId: $serviceUserId,
            queryListDTO: new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            ));
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $pagination = $result->getData();
        return RestResponse::success(data: GoogleAdsAccountResource::collection($pagination)->response()->getData());
    }

    /**
     * Lấy danh sách chiến dịch quảng cáo theo service user id và account id
     * @param string $serviceUserId
     * @param string $accountId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaigns(string $serviceUserId, string $accountId, Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->googleAdsService->getCampaignsPaginatedByServiceUserIdAndAccountId(
            serviceUserId: $serviceUserId,
            accountId: $accountId,
            queryListDTO: new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            ));
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $pagination = $result->getData();
        return RestResponse::success(data: GoogleAdsCampaignResource::collection($pagination)->response()->getData());
    }

    /**
     * Lấy thông tin chi tiết chiến dịch quảng cáo theo service user id và campaign id
     * @param string $serviceUserId
     * @param string $campaignId
     * @return JsonResponse
     */
    public function detailCampaign(string $serviceUserId, string $campaignId): JsonResponse
    {
        $result = $this->googleAdsService->getCampaignDetail($serviceUserId, $campaignId);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        return RestResponse::success(data: $result->getData());
    }

    /**
     * Lấy thông tin chi tiết về hiệu suất quảng cáo cho một chiến dịch cụ thể
     * @param string $serviceUserId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignInsights(string $serviceUserId, string $campaignId, Request $request): JsonResponse
    {
        $datePreset = $request->get('date_preset', 'last_7d');
        $result = $this->googleAdsService->getCampaignDailyInsights($serviceUserId, $campaignId, $datePreset);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        return RestResponse::success(data: $result->getData());
    }
}

