<?php

namespace App\Service;

use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\ServicePackageAllowedUserRepository;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ServicePackageService
{
    public function __construct(
        protected ServicePackageRepository $servicePackageRepository,
        protected ServicePackageAllowedUserRepository $servicePackageAllowedUserRepository,
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
            $query->withCount([
                'accountInventories as inventory_total_count',
                'accountInventories as inventory_available_count' => fn ($inventoryQuery) => $inventoryQuery->where('status', 'available'),
            ]);
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
            $allowedUserIds = $this->extractAllowedUserIds($form);
            unset($form['allowed_user_ids']);

            $data = $this->servicePackageRepository->create($form);
            $this->syncAllowedUsersForPaymentType(
                servicePackageId: $data->id,
                paymentType: $data->payment_type,
                allowedUserIds: $allowedUserIds
            );

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
            $data = $this->servicePackageRepository->query()
                ->withCount([
                    'accountInventories as inventory_total_count',
                    'accountInventories as inventory_available_count' => fn ($inventoryQuery) => $inventoryQuery->where('status', 'available'),
                ])
                ->find($id);
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

            $allowedUserIds = $this->extractAllowedUserIds($form);
            unset($form['allowed_user_ids']);

            $data->update($form);
            $this->syncAllowedUsersForPaymentType(
                servicePackageId: $data->id,
                paymentType: $data->payment_type,
                allowedUserIds: $allowedUserIds
            );

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

    public function getAllowedUserIds(string $id): ServiceReturn
    {
        try {
            return ServiceReturn::success(
                data: $this->servicePackageAllowedUserRepository->getAllowedUserIdsByPackageId($id)
            );
        } catch (\Exception $exception) {
            Logging::error(
                message: 'ServicePackageService@getAllowedUserIds error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    public function canUserUsePostpay(object $package, int|string $userId): bool
    {
        $paymentType = $package->payment_type ?? ServicePackagePaymentType::PREPAY->value;
        if ($paymentType === ServicePackagePaymentType::POSTPAY->value) {
            return true;
        }

        return $this->servicePackageAllowedUserRepository->isUserAllowed($package->id, $userId);
    }

    public function filterPackagesForUser(iterable $packages, int|string $userId): Collection
    {
        return collect($packages)
            ->map(function ($package) use ($userId) {
                $package->setAttribute('can_use_postpay', $this->canUserUsePostpay($package, $userId));

                return $package;
            })
            ->values();
    }

    private function extractAllowedUserIds(array $form): array
    {
        return collect($form['allowed_user_ids'] ?? [])
            ->filter(fn ($userId) => $userId !== null && $userId !== '')
            ->map(fn ($userId) => (string) $userId)
            ->unique()
            ->values()
            ->all();
    }

    private function syncAllowedUsersForPaymentType(int|string $servicePackageId, ?string $paymentType, array $allowedUserIds): void
    {
        if ($paymentType !== ServicePackagePaymentType::PREPAY->value) {
            $allowedUserIds = [];
        }

        $this->servicePackageAllowedUserRepository->syncAllowedUsers($servicePackageId, $allowedUserIds);
    }

}
