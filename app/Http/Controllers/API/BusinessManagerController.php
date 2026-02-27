<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Service\BusinessManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessManagerController extends Controller
{
    public function __construct(
        protected BusinessManagerService $businessManagerService,
    ) {
    }

    /**
     * API lấy danh sách Business Manager / MCC
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        $filter['view'] = 'bm';

        $result = $this->businessManagerService->getListBusinessManagers(
            new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $filter,
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        $data = $result->getData();
        $paginator = null;
        $stats = [
            'total_accounts' => 0,
            'active_accounts' => 0,
            'disabled_accounts' => 0,
            'by_platform' => [],
        ];

        if (is_array($data) && isset($data['paginator'])) {
            $paginator = $data['paginator'];
            $stats = $data['stats'] ?? $stats;
        } else {
            $paginator = $data;
        }

        if (!$paginator) {
            return RestResponse::success(data: [
                'paginator' => [
                    'data' => [],
                    'links' => [
                        'first' => null,
                        'last' => null,
                        'prev' => null,
                        'next' => null,
                    ],
                    'meta' => [
                        'links' => [],
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => 1,
                        'per_page' => $params->get('per_page', 10),
                        'to' => null,
                        'total' => 0,
                    ],
                ],
                'stats' => $stats,
                'childManagers' => $this->businessManagerService->getChildManagersForFilter(),
            ]);
        }

        $laravelArray = $paginator->toArray();

        $paginatorArray = [
            'data' => $laravelArray['data'] ?? [],
            'links' => [
                'first' => $laravelArray['first_page_url'] ?? null,
                'last' => $laravelArray['last_page_url'] ?? null,
                'prev' => $laravelArray['prev_page_url'] ?? null,
                'next' => $laravelArray['next_page_url'] ?? null,
            ],
            'meta' => [
                'links' => array_map(function ($link) {
                    return [
                        'url' => $link['url'] ?? null,
                        'label' => $link['label'] ?? '',
                        'active' => $link['active'] ?? false,
                        'page' => $link['page'] ?? null,
                    ];
                }, $laravelArray['links'] ?? []),
                'current_page' => $laravelArray['current_page'] ?? 1,
                'from' => $laravelArray['from'] ?? null,
                'last_page' => $laravelArray['last_page'] ?? 1,
                'per_page' => $laravelArray['per_page'] ?? 10,
                'to' => $laravelArray['to'] ?? null,
                'total' => $laravelArray['total'] ?? 0,
            ],
        ];

        return RestResponse::success(data: [
            'paginator' => $paginatorArray,
            'stats' => $stats,
            'childManagers' => $this->businessManagerService->getChildManagersForFilter(),
        ]);
    }
}

