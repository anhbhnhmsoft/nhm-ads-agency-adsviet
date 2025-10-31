<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(protected UserRepository $userRepository)
    {

    }

    /**
     * Lấy danh sách nhân viên có phân trang
     * Chỉ cho phép lấy danh sách nhân viên system
     * @param QueryListDTO $queryListDTO
     * @return ServiceReturn
     */
    public function getListEmployeePagination(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            $filter['roles'] = [
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ];

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
}
