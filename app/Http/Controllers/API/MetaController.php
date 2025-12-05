<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Resources\MetaAdsCampaignResource;
use App\Service\MetaService;
use FacebookAds\Object\Values\AdDatePresetValues;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\MetaAdsAccountResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class MetaController extends Controller
{

    public function __construct(
        protected MetaService $metaService,
    )
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


    /**
     * Lấy thông tin chi tiết chiến dịch quảng cáo theo service user id và campaign id
     * @param string $serviceUserId
     * @param string $campaignId
     * @return JsonResponse
     */
    public function detailCampaign(string $serviceUserId, string $campaignId): JsonResponse
    {
        $result = $this->metaService->getCampaignDetail(
            serviceUserId: $serviceUserId,
            campaignId: $campaignId,
        );
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return RestResponse::success(data: $data);
    }

    public function getCampaignInsights(string $serviceUserId, string $campaignId, Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'date_preset' => ['required', Rule::in([
                AdDatePresetValues::LAST_7D,
                AdDatePresetValues::LAST_14D,
                AdDatePresetValues::LAST_30D,
                AdDatePresetValues::LAST_28D,
                AdDatePresetValues::LAST_90D
            ])],
        ],[
            'date_preset.required' => __('meta.error.date_preset_invalid'),
            'date_preset.in' => __('meta.error.date_preset_invalid'),
        ]);
        if ($validate->fails()) {
            return RestResponse::error(
                message: $validate->errors()->first(),
            );
        }
        $result = $this->metaService->getCampaignDailyInsights(
            serviceUserId: $serviceUserId,
            campaignId: $campaignId,
            datePreset: $request->input('date_preset'),
        );
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return RestResponse::success(data: $data);
    }

    /**
     * Tạm dừng / bật lại chiến dịch Meta trực tiếp qua API (không tạo transaction ví).
     */
    public function updateCampaignStatus(string $serviceUserId, string $campaignId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $result = $this->metaService->updateCampaignStatus(
            serviceUserId: $serviceUserId,
            campaignId: $campaignId,
            status: $validated['status'],
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData());
    }

    /**
     * Cập nhật spend_cap (giới hạn chi tiêu) cho chiến dịch Meta.
     */
    public function updateCampaignSpendCap(string $serviceUserId, string $campaignId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $result = $this->metaService->updateCampaignSpendCap(
            serviceUserId: $serviceUserId,
            campaignId: $campaignId,
            amount: (float) $validated['amount'],
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData());
    }
}
