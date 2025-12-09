<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ServicePackageService
{
    public function __construct(
        protected ServicePackageRepository $servicePackageRepository,
        protected ServiceUserRepository $serviceUserRepository,
    )
    {
    }

    /**
     * Lấy danh sách gói dịch vụ
     * @param QueryListDTO $queryListDTO
     * @return ServiceReturn
     */
    public function getListServicePackage(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $query = $this->servicePackageRepository
                ->filterQuery($queryListDTO->filter);
            $query = $this->servicePackageRepository
                ->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        }
        catch (\Exception $exception){
            Logging::error(
                message: 'Lỗi lấy danh sách gói dịch vụ ServicePackageService@getListServicePackage: ' . $exception->getMessage(),
                exception: $exception
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
     * Tạo gói dịch vụ
     * @param array $form
     * @return ServiceReturn
     */
    public function createServicePackage(array $form): ServiceReturn
    {
        try {
            $data = $this->servicePackageRepository->create($form);
            return ServiceReturn::success(
                data: $data,
            );
        }catch (\Exception $exception){
            Logging::error(
                message: 'Lỗi tạo gói dịch vụ ServicePackageService@createServicePackage: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy gói dịch vụ theo id
     * @param string $id
     * @return ServiceReturn
     */
    public function getServicePackageById(string $id): ServiceReturn
    {
        try {
            $data = $this->servicePackageRepository->find($id);
            if (!$data) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            return ServiceReturn::success(data: $data);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy gói dịch vụ ServicePackageService@getServicePackageById: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }
    /**
     * Cập nhật gói dịch vụ
     * @param string $id
     * @param array $form
     * @return ServiceReturn
     */
    public function updateServicePackage(string $id, array $form): ServiceReturn
    {
        try {
            $data = $this->servicePackageRepository->find($id);
            if (!$data) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            $data->update($form);
            return ServiceReturn::success(data: $data);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi cập nhật gói dịch vụ ServicePackageService@updateServicePackage: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Xóa gói dịch vụ
     * @param string $id
     * @return ServiceReturn
     */
    public function deleteServicePackage(string $id): ServiceReturn
    {
        try {
            $inUse = $this->serviceUserRepository->query()
                ->where('package_id', $id)
                ->exists();
            if ($inUse) {
                return ServiceReturn::error(__('services.validation.package_in_use'));
            }

            $this->servicePackageRepository->delete($id);
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi xóa gói dịch vụ ServicePackageService@deleteServicePackage: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Bật/Tắt gói dịch vụ
     * @param string $id
     * @return ServiceReturn
     */
    public function toggleDisable(string $id): ServiceReturn
    {
        try {
            $package = $this->servicePackageRepository->find($id);
            if (!$package) {
                return ServiceReturn::error(__('common_error.not_found'));
            }
            $package->disabled = !$package->disabled;
            $package->save();
            return ServiceReturn::success(data: $package);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi thay đổi trạng thái gói dịch vụ ServicePackageService@toggleDisable: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }
}
