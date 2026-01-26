<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Requests\Supplier\SupplierStoreRequest;
use App\Http\Requests\Supplier\SupplierUpdateRequest;
use App\Http\Resources\SupplierListResource;
use App\Http\Resources\SupplierResource;
use App\Service\SupplierService;
use Illuminate\Http\Request;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService,
    ) {
    }

    /**
     * Hiển thị danh sách nhà cung cấp
     */
    public function index(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->supplierService->getListSupplier(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));

        return $this->rendering(
            view: 'supplier/index',
            data: [
                'paginator' => fn () => SupplierListResource::collection($result->getData()),
            ]
        );
    }

    /**
     * Hiển thị form tạo nhà cung cấp
     */
    public function createView(): Response
    {
        return $this->rendering(
            view: 'supplier/create',
            data: []
        );
    }

    /**
     * Xử lý tạo nhà cung cấp
     */
    public function create(SupplierStoreRequest $request): RedirectResponse
    {
        $form = $request->validated();
        $result = $this->supplierService->createSupplier($form);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('suppliers_index')->with('success', 'Tạo nhà cung cấp thành công');
    }

    /**
     * Hiển thị form chỉnh sửa nhà cung cấp
     */
    public function editView(string $id): Response|RedirectResponse
    {
        $result = $this->supplierService->getSupplierById($id);
        if ($result->isError()) {
            return redirect()->route('suppliers_index')->withErrors(['error' => $result->getMessage()]);
        }

        return $this->rendering(
            view: 'supplier/edit',
            data: [
                'supplier' => fn () => SupplierResource::make($result->getData())->toArray(request()),
            ]
        );
    }

    /**
     * Xử lý cập nhật nhà cung cấp
     */
    public function update(string $id, SupplierUpdateRequest $request): RedirectResponse
    {
        $form = $request->validated();
        $result = $this->supplierService->updateSupplier($id, $form);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('suppliers_index')->with('success', 'Cập nhật nhà cung cấp thành công');
    }

    /**
     * Xóa nhà cung cấp
     */
    public function destroy(string $id): RedirectResponse
    {
        $result = $this->supplierService->deleteSupplier($id);
        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }
        return redirect()->route('suppliers_index')->with('success', 'Xóa nhà cung cấp thành công');
    }

    /**
     * Toggle disabled status
     */
    public function toggleDisable(string $id): RedirectResponse
    {
        $result = $this->supplierService->toggleDisable($id);
        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }
        return back()->with('success', 'Cập nhật trạng thái thành công');
    }
}

