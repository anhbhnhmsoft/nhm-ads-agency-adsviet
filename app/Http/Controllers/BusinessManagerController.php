<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Resources\BusinessManagerListResource;
use App\Service\BusinessManagerService;
use App\Service\TicketService;
use App\Common\Constants\Ticket\TicketMetadataType;
use App\Common\Constants\Ticket\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class BusinessManagerController extends Controller
{
    public function __construct(
        protected BusinessManagerService $businessManagerService,
        protected TicketService $ticketService,
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

    /**
     * Lấy danh sách BM con của một BM gốc
     */
    public function getChildBusinessManagers(string $parentBmId)
    {
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

    /**
     * Nạp tiền vào BM/MCC (tạo ticket)
     */
    public function topUp(string $bmId, Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        // Lấy thông tin BM/MCC để lấy platform
        $bmResult = $this->businessManagerService->getAccountsByBmId($bmId);
        if ($bmResult->isError() || !$bmResult->getData()) {
            return back()->withErrors(['error' => __('business_manager.top_up_dialog.bm_not_found', ['default' => 'Không tìm thấy BM/MCC'])]);
        }

        $accounts = $bmResult->getData();
        if (empty($accounts)) {
            return back()->withErrors(['error' => __('business_manager.top_up_dialog.no_accounts', ['default' => 'BM/MCC chưa có tài khoản'])]);
        }

        // Lấy platform từ account đầu tiên
        $firstAccount = $accounts[0];
        $platform = $firstAccount['platform'] ?? null;

        if (!$platform) {
            return back()->withErrors(['error' => __('business_manager.top_up_dialog.platform_not_found', ['default' => 'Không xác định được platform'])]);
        }

        // Tạo ticket tương tự như deposit-app
        $ticketData = [
            'subject' => __('business_manager.top_up_dialog.ticket_subject', [
                'default' => 'Yêu cầu nạp tiền vào BM/MCC',
                'bm_id' => $bmId,
            ]),
            'description' => $request->input('note', ''),
            'priority' => TicketPriority::HIGH->value,
            'metadata' => [
                'type' => TicketMetadataType::WALLET_DEPOSIT_APP->value,
                'platform' => (int) $platform,
                'bm_id' => $bmId,
                'amount' => (float) $request->input('amount'),
                'notes' => $request->input('note'),
            ],
        ];

        $result = $this->ticketService->createTicket($ticketData);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return back()->with('success', __('business_manager.top_up_dialog.success'));
    }
}

