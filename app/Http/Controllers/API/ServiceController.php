<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Resources\ServicePackageResource;
use App\Service\ServicePackageService;

class ServiceController extends Controller
{
   public function __construct(protected ServicePackageService $servicePackageService)
   {
   }

   public function package(): \Illuminate\Http\JsonResponse
   {
        $result = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: request()->get('per_page', 10),
            page: request()->get('page', 1),
            filter: [
                'is_active' => true
            ],
            sortBy: 'created_at',
            sortDirection: 'desc',
        ));
        $pagination = $result->getData();
        return RestResponse::success(data: ServicePackageResource::collection($pagination)->response()->getData());
   }
}
