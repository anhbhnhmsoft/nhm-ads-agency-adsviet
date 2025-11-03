<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Resources\ListCustomerResource;
use App\Http\Resources\ListEmployeeResource;
use App\Service\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;


class UserController extends Controller
{

    public function __construct(protected UserService $userService)
    {

    }

    public function listCustomer(Request $request)
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->userService->getListCustomerPagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        $paginator = $result->getData();
        return $this->rendering(
            view: 'user/list-customer',
            data: [
                'paginator' => fn () => ListCustomerResource::collection($paginator),
            ]
        );
    }

    public function listEmployee(Request $request)
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->userService->getListEmployeePagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        $paginator = $result->getData();
        return $this->rendering(
            view: 'user/list-employee',
            data: [
                'paginator' => fn () => ListEmployeeResource::collection($paginator),
            ]
        );
    }

    public function createEmployeeScreen(Request $request)
    {
        return $this->rendering(
            view: 'user/create-employee',
        );
    }
}
