<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Common\Constants\User\UserRole;
use App\Http\Resources\BusinessManagerListResource;
use App\Service\BusinessManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\MetaBusinessManager;

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
        $this->ensureInternalAccess();

        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        $filter['view'] = 'bm';
        $isAdmin = (int) $request->user()?->role === UserRole::ADMIN->value;
        $hiddenBusinessManagers = $isAdmin ? $this->getHiddenMetaBusinessManagers() : [];

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
            // Nếu service trả về cả paginator và stats
            if (is_array($data) && isset($data['paginator'])) {
                $paginator = $data['paginator'];
                $stats = $data['stats'] ?? $stats;
            } else {
                $paginator = $data;
            }
        }

        if (!$paginator) {
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
                    'stats' => fn () => $stats,
                    'childManagers' => fn () => $this->businessManagerService->getChildManagersForFilter(),
                    'hiddenBusinessManagers' => fn () => $hiddenBusinessManagers,
                ]
            );
        }

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
                'stats' => fn () => $stats,
                'childManagers' => fn () => $this->businessManagerService->getChildManagersForFilter(),
                'hiddenBusinessManagers' => fn () => $hiddenBusinessManagers,
            ]
        );
    }

    private function getHiddenMetaBusinessManagers(): array
    {
        return MetaBusinessManager::query()
            ->whereNotNull('hidden_at')
            ->orderByDesc('hidden_at')
            ->get(['bm_id', 'parent_bm_id', 'name', 'hidden_at'])
            ->map(fn (MetaBusinessManager $bm) => [
                'id' => (string) $bm->bm_id,
                'bm_ids' => [(string) $bm->bm_id],
                'bm_name' => $bm->name ?: (string) $bm->bm_id,
                'name' => $bm->name ?: (string) $bm->bm_id,
                'parent_bm_id' => $bm->parent_bm_id ? (string) $bm->parent_bm_id : null,
                'platform' => \App\Common\Constants\Platform\PlatformType::META->value,
                'hidden_at' => optional($bm->hidden_at)->toISOString(),
            ])
            ->toArray();
    }

    /**
     * Lấy danh sách accounts của một BM/MCC
     * @param string $bmId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccounts(string $bmId, Request $request)
    {
        $this->ensureInternalAccess();

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

    /**
     * Lấy danh sách BM con của một BM gốc
     */
    public function getChildBusinessManagers(string $parentBmId)
    {
        $this->ensureInternalAccess();

        $result = $this->businessManagerService->getChildBusinessManagers($parentBmId);

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

    public function hideMetaBusinessManager(string $bmId): RedirectResponse
    {
        if ((int) auth()->user()?->role !== UserRole::ADMIN->value) {
            abort(403);
        }

        $bm = MetaBusinessManager::query()
            ->where('bm_id', $bmId)
            ->first();

        if (!$bm) {
            return back()->withErrors(['error' => __('business_manager.bm_not_found')]);
        }

        $bm->forceFill(['hidden_at' => now()])->save();

        return back()->with('success', __('business_manager.hide_success'));
    }

    public function restoreMetaBusinessManager(string $bmId): RedirectResponse
    {
        if ((int) auth()->user()?->role !== UserRole::ADMIN->value) {
            abort(403);
        }

        $bm = MetaBusinessManager::query()
            ->where('bm_id', $bmId)
            ->first();

        if (!$bm) {
            return back()->withErrors(['error' => __('business_manager.bm_not_found')]);
        }

        $bm->forceFill(['hidden_at' => null])->save();

        return back()->with('success', __('business_manager.restore_success'));
    }

    private function ensureInternalAccess(): void
    {
        if (!in_array((int) auth()->user()?->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ], true)) {
            abort(403);
        }
    }

}
