<?php

namespace App\Http\Controllers;

use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Resources\ServiceOrderResource;
use App\Service\ServiceUserService;
use Illuminate\Http\Request;

class ServiceManagementController extends Controller
{
    public function __construct(
        protected ServiceUserService $serviceUserService,
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        $filter['status'] = $filter['status'] ?? ServiceUserStatus::ACTIVE->value;

        $result = $this->serviceUserService->getListServiceUserPagination(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $filter,
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));

        return $this->rendering(
            view: 'service-management/index',
            data: [
                'paginator' => fn () => ServiceOrderResource::collection($result->getData()),
            ]
        );
    }
}

