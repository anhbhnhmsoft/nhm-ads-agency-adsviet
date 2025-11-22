<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\UserRepository;
use App\Repositories\UserReferralRepository;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Common\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserReferralRepository $userReferralRepository
    )
    {
    }

    public function getCustomerSummaryForDashboard(): ServiceReturn
    {
        try {
            $baseQuery = $this->userRepository->query()->where('role', UserRole::CUSTOMER->value);
            $totalCustomers = (clone $baseQuery)->count();
            $activeCustomers = (clone $baseQuery)->where('disabled', false)->count();

            return ServiceReturn::success(data: [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'Lỗi khi lấy thống kê khách hàng UserService@getCustomerSummaryForDashboard: '.$exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách nhân viên có phân trang
     * Chỉ cho phép lấy danh sách nhân viên system
     * @param QueryListDTO $queryListDTO
     * @return ServiceReturn
     */
    public function getListEmployeePagination(QueryListDTO $queryListDTO): ServiceReturn
    {
        $currentUser = Auth::user();
        try {
            $filter = $queryListDTO->filter ?? [];
            $filter['roles'] = [
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ];

            // Nếu là manager, chỉ lấy employees được gán cho manager đó
            if ($currentUser && $currentUser->role === UserRole::MANAGER->value) {
                $filter['manager_id'] = $currentUser->id;
            }

            $query = $this->userRepository->filterQuery($filter);
            $query = $this->userRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách nhân viên UserService@getListEmployeePagination: ' . $exception->getMessage(),
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

    public function getListCustomerPagination(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            $filter['roles'] = [
                UserRole::CUSTOMER->value,
                UserRole::AGENCY->value,
            ];

            // Phân quyền hiển thị danh sách khách hàng theo vai trò người dùng hiện tại
            $currentUser = Auth::user();
            if ($currentUser) {
                switch ($currentUser->role) {
                    case UserRole::MANAGER->value:
                        // Manager: xem khách hàng do mình quản lý + khách hàng do nhân viên của mình quản lý
                        $employeeIds = $this->userReferralRepository
                            ->getAssignedEmployeeIds((int)$currentUser->id);
                        $filter['referrer_ids'] = array_values(array_unique(array_merge([$currentUser->id], $employeeIds)));
                        break;
                    case UserRole::EMPLOYEE->value:
                        // Employee: chỉ xem khách hàng do chính mình quản lý
                        $filter['referrer_ids'] = [$currentUser->id];
                        break;
                    case UserRole::AGENCY->value:
                        // Agency: chỉ xem khách hàng do mình quản lý, và không hiển thị bản thân
                        $filter['referrer_ids'] = [$currentUser->id];
                        $filter['exclude_user_id'] = $currentUser->id;
                        break;
                }
            }
            $query = $this->userRepository->filterQuery($filter);
            $query = $this->userRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách khách hàng UserService@getListCustomerPagination: ' . $exception->getMessage(),
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

    public function createEmployee(array $data): ServiceReturn
    {
        try {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => (int) $data['role'],
                'disabled' => (bool) ($data['disabled'] ?? false),
                'referral_code' => Helper::generateReferCodeUser(UserRole::from((int)$data['role'])),
            ]);

            return ServiceReturn::success(data: ['id' => $user->id]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi tạo nhân viên UserService@createEmployee: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function updateEmployee(string $id, array $data): ServiceReturn
    {
        $user = $this->userRepository->findEmployeeById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $payload = [
                'name' => $data['name'] ?? $user->name,
                'username' => $data['username'] ?? $user->username,
                'phone' => $data['phone'] ?? $user->phone,
                'role' => isset($data['role']) ? (int)$data['role'] : $user->role,
                'disabled' => isset($data['disabled']) ? (bool)$data['disabled'] : $user->disabled,
            ];
            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }
            $user->update($payload);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi cập nhật nhân viên UserService@updateEmployee: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function updateUser(string $id, array $data): ServiceReturn
    {
        $user = $this->userRepository->findUserById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $payload = [
                'name' => $data['name'] ?? $user->name,
                'username' => $data['username'] ?? $user->username,
                'phone' => $data['phone'] ?? $user->phone,
                'role' => isset($data['role']) ? (int)$data['role'] : $user->role,
                'disabled' => isset($data['disabled']) ? (bool)$data['disabled'] : $user->disabled,
            ];
            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }
            $user->update($payload);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi cập nhật người dùng UserService@updateUser: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function deleteEmployee(string $id): ServiceReturn
    {
        $user = $this->userRepository->findEmployeeById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $user->delete();
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi xóa nhân viên UserService@deleteEmployee: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function deleteUser(string $id): ServiceReturn
    {
        $user = $this->userRepository->findUserById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $user->delete();
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi xóa người dùng UserService@deleteUser: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function toggleDisable(string $id, bool $disabled): ServiceReturn
    {
        $user = $this->userRepository->findEmployeeById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $user->update(['disabled' => $disabled]);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi đổi trạng thái nhân viên UserService@toggleDisable: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function userToggleDisable(string $id, bool $disabled): ServiceReturn
    {
        $user = $this->userRepository->findUserById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $user->update(['disabled' => $disabled]);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi đổi trạng thái người dùng UserService@UserToggleDisable: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function findEmployee(string $id): ServiceReturn
    {
        $user = $this->userRepository->findEmployeeById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        return ServiceReturn::success(data: [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'phone' => $user->phone,
            'role' => $user->role,
            'disabled' => $user->disabled,
        ]);
    }

    public function findUser(string $id): ServiceReturn
    {
        $user = $this->userRepository->findUserById($id);
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        return ServiceReturn::success(data: [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'phone' => $user->phone,
            'role' => $user->role,
            'disabled' => $user->disabled,
        ]);
    }

    public function getManagers(): ServiceReturn
    {
        try {
            $managers = $this->userRepository->getManagers();
            return ServiceReturn::success(data: $managers->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'username' => $m->username,
            ])->toArray());
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi lấy danh sách managers UserService@getManagers: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function getEmployeesForAssignment(string $managerId): ServiceReturn
    {
        try {
            $employees = $this->userRepository->getEmployees();
            $assignedIds = $this->userReferralRepository->getAssignedEmployeeIds((int)$managerId);

            $result = $employees->map(function ($emp) use ($assignedIds) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'username' => $emp->username,
                    'assigned' => in_array($emp->id, $assignedIds),
                ];
            });

            return ServiceReturn::success(data: $result->toArray());
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi lấy danh sách employees UserService@getEmployeesForAssignment: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function assignEmployee(string $managerId, string $employeeId): ServiceReturn
    {
        try {
            $manager = $this->userRepository->find($managerId);
            $employee = $this->userRepository->find($employeeId);

            if (!$manager || $manager->role !== UserRole::MANAGER->value) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            if (!$employee || $employee->role !== UserRole::EMPLOYEE->value) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            $this->userReferralRepository->assignEmployeeToManager((int)$employeeId, (int)$managerId);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi gán employee UserService@assignEmployee: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function unassignEmployee(string $managerId, string $employeeId): ServiceReturn
    {
        try {
            $this->userReferralRepository->unassignEmployeeFromManager((int)$employeeId, (int)$managerId);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'Lỗi khi hủy gán employee UserService@unassignEmployee: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
