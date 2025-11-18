<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Resources\MetaAdsCampaignResource;
use App\Service\MetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\MetaAdsAccountResource;


class MetaController extends Controller
{

    public function __construct(protected MetaService $metaService)
    {
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
        $result = $this->metaService->getAdsAccountPaginatedByServiceUserId(
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
        return RestResponse::success(data: MetaAdsAccountResource::collection($pagination)->response()->getData());

    }

    public function getCampaigns(string $serviceUserId, string $accountId, Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->metaService->getCampaignsPaginatedByServiceUserIdAndAccountId(
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
        return RestResponse::success(data: MetaAdsCampaignResource::collection($pagination)->response()->getData());
    }
}
