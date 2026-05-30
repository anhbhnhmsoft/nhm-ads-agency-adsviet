<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Http\Resources\ServiceAccountInventoryResource;
use App\Service\ServiceAccountInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAccountInventoryController extends Controller
{
    public function __construct(
        protected ServiceAccountInventoryService $inventoryService,
    ) {
    }

    public function index(string $packageId): JsonResponse
    {
        $result = $this->inventoryService->listForPackage($packageId);

        return RestResponse::success(
            ServiceAccountInventoryResource::collection($result->getData())->resolve()
        );
    }

    public function import(Request $request, string $packageId): JsonResponse
    {
        $data = $request->validate([
            'accounts' => ['required', 'array', 'min:1', 'max:500'],
            'accounts.*.account_id' => ['required', 'string', 'max:255'],
            'accounts.*.account_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.business_manager_id' => ['nullable', 'string', 'max:255'],
            'accounts.*.customer_manager_id' => ['nullable', 'string', 'max:255'],
            'accounts.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->inventoryService->importForPackage($packageId, $data['accounts']);
        if ($result->isError()) {
            return RestResponse::error($result->getMessage(), 422);
        }

        return RestResponse::success($result->getData());
    }

    public function destroy(string $packageId, string $inventoryId): JsonResponse
    {
        $result = $this->inventoryService->delete($inventoryId);
        if ($result->isError()) {
            return RestResponse::error($result->getMessage(), 422);
        }

        return RestResponse::success();
    }
}
