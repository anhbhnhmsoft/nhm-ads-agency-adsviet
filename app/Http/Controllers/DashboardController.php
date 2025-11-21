<?php

namespace App\Http\Controllers;

use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Service\MetaService;
use App\Service\UserService;
use App\Service\WalletTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected MetaService $metaService,
        protected UserService $userService,
        protected WalletTransactionService $walletTransactionService,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // lấy dữ liệu dashboard cho role agency và customer
        if ($user && in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
            $result = $this->metaService->getDashboardData();
            $dashboardData = $result->isSuccess() ? $result->getData() : null;
            $dashboardError = $result->isError() ? $result->getMessage() : null;
            
            return $this->rendering('dashboard/index', [
                'dashboardData' => $dashboardData,
                'dashboardError' => $dashboardError,
            ]);
        }

        if ($user && in_array($user->role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
            $customerSummaryResult = $this->userService->getCustomerSummaryForDashboard();
            $pendingPage = max(1, (int) $request->input('pending_page', 1));
            $pendingQuery = new QueryListDTO(
                perPage: 20,
                page: $pendingPage,
                filter: [],
                sortBy: 'created_at',
                sortDirection: 'desc',
            );
            $pendingTransactionsResult = $this->walletTransactionService->getPendingTransactionsPaginated($pendingQuery);

            $customerSummary = $customerSummaryResult->isSuccess()
                ? $customerSummaryResult->getData()
                : ['total_customers' => 0, 'active_customers' => 0];

            $pendingPaginator = $pendingTransactionsResult->isSuccess()
                ? $pendingTransactionsResult->getData()
                : null;

            $dashboardError = null;
            if ($customerSummaryResult->isError()) {
                $dashboardError = $customerSummaryResult->getMessage();
            }
            if ($pendingTransactionsResult->isError()) {
                $dashboardError = $dashboardError
                    ? $dashboardError.' '.$pendingTransactionsResult->getMessage()
                    : $pendingTransactionsResult->getMessage();
            }

            $adminDashboardData = [
                'total_customers' => $customerSummary['total_customers'],
                'active_customers' => $customerSummary['active_customers'],
                'pending_transactions' => $pendingPaginator ? $pendingPaginator->total() : 0,
            ];

            $pendingPaginationPayload = null;
            if ($pendingPaginator) {
                $pendingPaginationPayload = [
                    'data' => $pendingPaginator->items(),
                    'links' => [
                        'first' => $pendingPaginator->url(1),
                        'last' => $pendingPaginator->url($pendingPaginator->lastPage()),
                        'next' => $pendingPaginator->nextPageUrl(),
                        'prev' => $pendingPaginator->previousPageUrl(),
                    ],
                    'meta' => [
                        'current_page' => $pendingPaginator->currentPage(),
                        'from' => $pendingPaginator->firstItem(),
                        'last_page' => $pendingPaginator->lastPage(),
                        'per_page' => $pendingPaginator->perPage(),
                        'to' => $pendingPaginator->lastItem(),
                        'total' => $pendingPaginator->total(),
                    ],
                ];
            }

            return $this->rendering('dashboard/index', [
                'adminDashboardData' => $adminDashboardData,
                'adminPendingTransactions' => $pendingPaginationPayload,
                'dashboardError' => $dashboardError,
            ]);
        }
        
        return $this->rendering('dashboard/index', []);
    }
}
