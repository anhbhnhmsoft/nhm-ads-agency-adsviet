<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Service\BusinessManagerService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceManagementController extends Controller
{
    public function __construct(
        protected BusinessManagerService $businessManagerService,
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        // Trang quản lý tài khoản: hiển thị theo từng account
        $filter['view'] = 'account';

        $result = $this->businessManagerService->getListBusinessManagers(
            new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $filter,
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $data = $result->isError() ? null : $result->getData();
        $paginator = null;
        $stats = [
            'total_accounts' => 0,
            'active_accounts' => 0,
            'disabled_accounts' => 0,
            'by_platform' => [],
        ];
        if ($data) {
            if (is_array($data) && isset($data['paginator'])) {
                $paginator = $data['paginator'];
                $stats = $data['stats'] ?? $stats;
            } elseif ($data instanceof LengthAwarePaginator) {
                $paginator = $data;
            }
        }

        if (!$paginator) {
            return $this->rendering(
                view: 'service-management/index',
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
                    'stats' => fn () => $stats,
                ]
            );
        }

        $paginatorData = $paginator->toArray();
        $paginatorArray = [
            'data' => $paginatorData['data'] ?? [],
            'links' => [
                'first' => $paginatorData['first_page_url'] ?? null,
                'last' => $paginatorData['last_page_url'] ?? null,
                'prev' => $paginatorData['prev_page_url'] ?? null,
                'next' => $paginatorData['next_page_url'] ?? null,
            ],
            'meta' => [
                'links' => array_map(function ($link) {
                    return [
                        'url' => $link['url'] ?? null,
                        'label' => $link['label'] ?? '',
                        'active' => $link['active'] ?? false,
                        'page' => $link['page'] ?? null,
                    ];
                }, $paginatorData['links'] ?? []),
                'current_page' => $paginatorData['current_page'] ?? 1,
                'from' => $paginatorData['from'] ?? null,
                'last_page' => $paginatorData['last_page'] ?? 1,
                'per_page' => $paginatorData['per_page'] ?? 10,
                'to' => $paginatorData['to'] ?? null,
                'total' => $paginatorData['total'] ?? 0,
            ],
        ];

        return $this->rendering(
            view: 'service-management/index',
            data: [
                'paginator' => fn () => $paginatorArray,
                'stats' => fn () => $stats,
            ]
        );
    }
}

