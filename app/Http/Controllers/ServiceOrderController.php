<?php

namespace App\Http\Controllers;

use App\Common\Helpers\TimezoneHelper;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Resources\ServiceOrderResource;
use App\Http\Requests\Service\ServiceOrderApproveRequest;
use App\Http\Requests\Service\ServiceOrderUpdateConfigRequest;
use App\Service\ServiceUserService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ServiceOrderController extends Controller
{
    public function __construct(
        protected ServiceUserService $serviceUserService,
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];

        $result = $this->serviceUserService->getListServiceUserPagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $filter,
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));

        return $this->rendering(
            view: 'service-order/index',
            data: [
                'paginator' => fn () => ServiceOrderResource::collection($result->getData()),
                'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
                'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
            ]
        );
    }

    /**
     * Admin/Manager/Employee xác nhận đơn dịch vụ
     */
    public function approve(ServiceOrderApproveRequest $request, string $id): RedirectResponse
    {
        $result = $this->serviceUserService->approveServiceUser($id, $request->validated());
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
        } else {
            FlashMessage::success(__('services.flash.order_approve_success'));
        }

        return redirect()->back();
    }

    /**
     * Admin/Manager/Employee hủy đơn dịch vụ
     */
    public function cancel(string $id): RedirectResponse
    {
        $result = $this->serviceUserService->cancelServiceUser($id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
        } else {
            FlashMessage::success(__('services.flash.order_cancel_success'));
        }

        return redirect()->back();
    }

    // Cập nhật config_account của đơn dịch vụ
    public function updateConfig(ServiceOrderUpdateConfigRequest $request, string $id): RedirectResponse
    {
        $result = $this->serviceUserService->updateConfigAccount($id, $request->validated());
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
        } else {
            FlashMessage::success(__('services.flash.config_update_success'));
        }

        return redirect()->back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $result = $this->serviceUserService->deleteServiceUser($id);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
        } else {
            FlashMessage::success(__('services.flash.order_delete_success'));
        }

        return redirect()->back();
    }
}

