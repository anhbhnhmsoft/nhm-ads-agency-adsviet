<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceUserService
{

    public function __construct(
        protected ServiceUserRepository    $serviceUserRepository,
        protected ServicePackageRepository $servicePackageRepository,
        protected UserRepository           $userRepository
    )
    {
    }

    public function getListServiceUserPagination(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $user = Auth::user();
            $filter = $queryListDTO->filter ?? [];
            if ($user->role === UserRole::CUSTOMER->value) {
                $filter['user_id'] = $user->id;
            }
            // Tạo query với bộ lọc
            $query = $this->serviceUserRepository->filterQuery($filter);
            // Lấy thông tin gói dịch vụ và người dùng
            $query->with(['package:id,name,platform', 'user:id,name']);
            // Sắp xếp
            $query = $this->serviceUserRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);

            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy danh sách gói dịch vụ ServiceUserService@getListPagination: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::success(
                data: new LengthAwarePaginator([], 0, $queryListDTO->perPage, $queryListDTO->page)
            );
        }
    }

}
