<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Resources\BusinessManagerListResource;
use App\Service\BusinessManagerService;
use Illuminate\Http\Request;

class BusinessManagerController extends Controller
{
    public function __construct(
        protected BusinessManagerService $businessManagerService,
    ) {
    }

    /**
     * Hiển thị danh sách Business Managers / MCC
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        
        $result = $this->businessManagerService->getListBusinessManagers(
            new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );
        
        if ($result->isError()) {
            // Trả về empty paginator nếu có lỗi
            return $this->rendering(
                view: 'business-manager/index',
                data: [
                    'paginator' => fn () => [
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
                ]
            );
        }
        
        $paginator = $result->getData();
        
        // Convert LengthAwarePaginator to LaravelPaginator format that frontend expects
        $laravelArray = $paginator->toArray();
        
        // Transform to frontend expected format
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
        
        return $this->rendering(
            view: 'business-manager/index',
            data: [
                'paginator' => fn () => $paginatorArray,
            ]
        );
    }

    /**
     * Lấy danh sách accounts của một BM/MCC
     * @param string $bmId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccounts(string $bmId, Request $request)
    {
        $platform = $request->input('platform') ? (int) $request->input('platform') : null;
        
        $result = $this->businessManagerService->getAccountsByBmId($bmId, $platform);
        
        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result->getData(),
        ]);
    }
}

