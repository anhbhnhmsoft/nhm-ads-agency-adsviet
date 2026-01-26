<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\SupplierRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function __construct(
        protected SupplierRepository $supplierRepository,
    ) {
    }

    /**
     * Lấy danh sách nhà cung cấp
     */
    public function getListSupplier(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $query = $this->supplierRepository->query();
            $filter = $queryListDTO->filter ?? [];
            $query = $this->supplierRepository->filterQuery($query, $filter);
            $query = $this->supplierRepository->sortQuery($query, $queryListDTO->sortBy ?? 'created_at', $queryListDTO->sortDirection ?? 'desc');
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy danh sách nhà cung cấp SupplierService@getListSupplier: ' . $exception->getMessage(),
                exception: $exception
            );
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
     * Tạo nhà cung cấp
     */
    public function createSupplier(array $form): ServiceReturn
    {
        try {
            $data = $this->supplierRepository->create($form);
            return ServiceReturn::success(data: $data);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi tạo nhà cung cấp SupplierService@createSupplier: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy nhà cung cấp theo id
     */
    public function getSupplierById(string $id): ServiceReturn
    {
        try {
            $data = $this->supplierRepository->query()->find($id);
            if (!$data) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            return ServiceReturn::success(data: $data);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy nhà cung cấp SupplierService@getSupplierById: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Cập nhật nhà cung cấp
     */
    public function updateSupplier(string $id, array $form): ServiceReturn
    {
        try {
            $data = $this->supplierRepository->query()->find($id);
            if (!$data) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            $data->update($form);
            return ServiceReturn::success(data: $data);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi cập nhật nhà cung cấp SupplierService@updateSupplier: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Xóa nhà cung cấp
     */
    public function deleteSupplier(string $id): ServiceReturn
    {
        try {
            $data = $this->supplierRepository->query()->find($id);
            if (!$data) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            $data->delete();
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi xóa nhà cung cấp SupplierService@deleteSupplier: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Toggle disabled status
     */
    public function toggleDisable(string $id): ServiceReturn
    {
        try {
            $supplier = $this->supplierRepository->query()->find($id);
            if (!$supplier) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            $supplier->disabled = !$supplier->disabled;
            $supplier->save();
            return ServiceReturn::success(data: $supplier);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi toggle disabled SupplierService@toggleDisable: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy tất cả nhà cung cấp active (dùng cho select)
     */
    public function getAllActiveSuppliers(): ServiceReturn
    {
        try {
            $suppliers = $this->supplierRepository->query()
                ->where('disabled', false)
                ->orderBy('name', 'asc')
                ->get();
            return ServiceReturn::success(data: $suppliers);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy danh sách nhà cung cấp active SupplierService@getAllActiveSuppliers: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::success(data: []);
        }
    }
}

