<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Requests\Commission\CommissionStoreRequest;
use App\Http\Requests\Commission\CommissionUpdateRequest;
use App\Http\Resources\CommissionResource;
use App\Service\CommissionService;
use Illuminate\Http\Request;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CommissionController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService,
    ) {
    }

    /**
     * Hiển thị danh sách cấu hình hoa hồng
     */
    public function index(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->commissionService->getListCommissions(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));

        return $this->rendering(
            view: 'commission/index',
            data: [
                'paginator' => fn () => CommissionResource::collection($result->getData()),
            ]
        );
    }

    /**
     * Hiển thị form tạo cấu hình hoa hồng
     */
    public function createView(): Response
    {
        $packagesResult = $this->commissionService->getServicePackages();
        if ($packagesResult->isError()) {
            return $this->rendering(
                view: 'commission/create',
                data: [
                    'packages' => collect(),
                    'error' => $packagesResult->getMessage(),
                ]
            );
        }

        return $this->rendering(
            view: 'commission/create',
            data: [
                'packages' => fn () => $packagesResult->getData(),
            ]
        );
    }

    /**
     * Xử lý tạo cấu hình hoa hồng
     */
    public function create(CommissionStoreRequest $request): RedirectResponse
    {
        $form = $request->validated();
        $result = $this->commissionService->createCommission($form);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('commissions_index')->with('success', 'Tạo cấu hình hoa hồng thành công');
    }

    /**
     * Hiển thị form chỉnh sửa cấu hình hoa hồng
     */
    public function editView(string $id): Response|RedirectResponse
    {
        $result = $this->commissionService->getCommissionById($id);
        if ($result->isError()) {
            return redirect()->route('commissions_index')->withErrors(['error' => $result->getMessage()]);
        }

        $packagesResult = $this->commissionService->getServicePackages();
        if ($packagesResult->isError()) {
            return redirect()->route('commissions_index')->withErrors(['error' => $packagesResult->getMessage()]);
        }

        return $this->rendering(
            view: 'commission/edit',
            data: [
                'commission' => fn () => $result->getData(),
                'packages' => fn () => $packagesResult->getData(),
            ]
        );
    }

    /**
     * Xử lý cập nhật cấu hình hoa hồng
     */
    public function update(string $id, CommissionUpdateRequest $request): RedirectResponse
    {
        $form = $request->validated();
        $result = $this->commissionService->updateCommission($id, $form);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('commissions_index')->with('success', 'Cập nhật cấu hình hoa hồng thành công');
    }

    /**
     * Xóa cấu hình hoa hồng
     */
    public function destroy(string $id): RedirectResponse
    {
        $result = $this->commissionService->deleteCommission($id);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('commissions_index')->with('success', 'Xóa cấu hình hoa hồng thành công');
    }
}

