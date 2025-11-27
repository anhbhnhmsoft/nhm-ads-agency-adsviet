<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\API\Service\ServicePurchaseApiRequest;
use App\Http\Resources\ServiceOwnerResource;
use App\Http\Resources\ServicePackageResource;
use App\Service\ServicePackageService;
use App\Service\ServicePurchaseService;
use App\Service\ServiceUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(
        protected ServicePackageService $servicePackageService,
        protected ServicePurchaseService $servicePurchaseService,
        protected ServiceUserService    $serviceUserService,
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

        $result = $this->servicePurchaseService->createPurchaseOrder(
            userId: (int) $user->id,
            packageId: $data['package_id'],
            topUpAmount: isset($data['top_up_amount']) ? (float) $data['top_up_amount'] : 0,
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

    }

}
