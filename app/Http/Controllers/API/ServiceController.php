<?php

namespace App\Http\Controllers\API;

use App\Common\Helper;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\API\Service\ServicePurchaseApiRequest;
use App\Http\Resources\ServiceOwnerResource;
use App\Http\Resources\ServicePackageResource;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use App\Service\ServicePackageService;
use App\Service\ServicePurchaseService;
use App\Service\ServiceUserService;
use FacebookAds\Object\Values\AdDatePresetValues;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function __construct(
        protected ServicePackageService $servicePackageService,
        protected ServicePurchaseService $servicePurchaseService,
        protected ServiceUserService    $serviceUserService,
        protected GoogleAdsService $googleAdsService,
        protected MetaService $metaService,
    )
    {
    }

    /**
     * Lấy danh sách dịch vụ đang sử dụng của người dùng
     * @param Request $request
     * @return JsonResponse
     */
    public function serviceOwner(Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->serviceUserService->getListServiceUserPagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        $pagination = $result->getData();
        return RestResponse::success(data: ServiceOwnerResource::collection($pagination)->response()->getData());
    }

    /**
     * Lấy danh sách gói dịch vụ
     * @param Request $request
     * @return JsonResponse
     */
    public function package(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: $request->get('per_page', 10),
            page: $request->get('page', 1),
            filter: [
                'is_active' => true
            ],
            sortBy: 'created_at',
            sortDirection: 'desc',
        ));
        $pagination = $result->getData();
        return RestResponse::success(data: ServicePackageResource::collection($pagination)->response()->getData());
    }

    /**
     * Đăng ký gói dịch vụ cho người dùng
     * @param Request $request
     */
    public function registerServicePackage(ServicePurchaseApiRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();

        $configAccount = [];
        if (isset($data['meta_email'])) {
            $configAccount['meta_email'] = $data['meta_email'];
        }
        if (isset($data['display_name'])) {
            $configAccount['display_name'] = $data['display_name'];
        }

        $result = $this->servicePurchaseService->createPurchaseOrder(
            userId: (int) $user->id,
            packageId: $data['package_id'],
            topUpAmount: isset($data['top_up_amount']) ? (float) $data['top_up_amount'] : 0,
            budget: isset($data['budget']) ? (float) $data['budget'] : 0,
            configAccount: $configAccount,
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $result->getData(),
            message: __('services.flash.purchase_success')
        );
    }

     /**
     * Lấy thông tin dashboard của người dùng
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());
        if ($platform === 'google_ads') {
            $result = $this->googleAdsService->getDashboardData();
        }else{
            $result = $this->metaService->getDashboardData();
        }
        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }
        return RestResponse::success(data: $result->getData());
    }

    public function report(Request $request): JsonResponse
    {
        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());
        if ($platform === 'google_ads') {
            $result = $this->googleAdsService->getReportData();
        }else{
            $result = $this->metaService->getReportData();
        }
        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }
        return RestResponse::success(data: $result->getData());
    }

    public function reportInsight(Request $request): JsonResponse
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
            return RestResponse::error(message: $validate->errors()->first(), status: 400);
        }

        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());
        if ($platform === 'google_ads') {
            $result = $this->googleAdsService->getReportInsights($validate->getData()['date_preset']);
        }else{
            $result = $this->metaService->getReportInsights($validate->getData()['date_preset']);
        }
        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }
        return RestResponse::success(data: $result->getData());
    }

}
