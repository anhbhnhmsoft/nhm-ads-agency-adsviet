<?php

namespace App\Http\Controllers;

use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use App\Service\UserService;
use App\Service\WalletTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
        protected UserService $userService,
        protected WalletTransactionService $walletTransactionService,
    ) {
    }

    // Hiển thị dashboard theo role của user
    public function index(Request $request)
    {
        $user = Auth::user();

        // Nếu không có user, trả về dashboard rỗng
        if (!$user) {
            return $this->rendering('dashboard/index', []);
        }

        // Agency và Customer: Dashboard quản lý dịch vụ quảng cáo (Meta Ads, Google Ads)
        if ($this->isAgencyOrCustomer($user)) {
            return $this->handleAgencyCustomerDashboard($request);
        }

        // Admin, Manager, Employee: Dashboard quản lý
        if ($this->isAdminOrStaff($user)) {
            return $this->handleAdminDashboard($request);
        }

        return $this->rendering('dashboard/index', []);
    }

    // Dữ liệu Dashboard cho Agency và Customer
    protected function handleAgencyCustomerDashboard(Request $request)
    {
        // Lấy platform từ request
        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());

        // Lấy data từ service
        $result = $this->getDashboardDataByPlatform($platform);

        $dashboardData = $result->isSuccess() ? $result->getData() : null;
        $dashboardError = $result->isError() ? $result->getMessage() : null;

        return $this->rendering('dashboard/index', [
            'dashboardData' => $dashboardData,
            'dashboardError' => $dashboardError,
            'selectedPlatform' => $platform,
        ]);
    }

    // Hiển thị dashboard theo role của Admin
    protected function handleAdminDashboard(Request $request)
    {
        // Lấy thống kê khách hàng
        $customerSummary = $this->getCustomerSummary();

        // Lấy danh sách giao dịch chờ duyệt
        $pendingTransactions = $this->getPendingTransactions($request);

        // Chuẩn bị data để trả về
        $adminDashboardData = [
            'total_customers' => $customerSummary['total_customers'],
            'active_customers' => $customerSummary['active_customers'],
            'pending_transactions' => $pendingTransactions['total'],
        ];

        return $this->rendering('dashboard/index', [
            'adminDashboardData' => $adminDashboardData,
            'adminPendingTransactions' => $pendingTransactions['pagination'],
            'dashboardError' => $pendingTransactions['error'],
        ]);
    }

    // Lấy dashboard data theo platform (Meta hoặc Google Ads)

    protected function getDashboardDataByPlatform(string $platform): ServiceReturn
    {
        if ($platform === 'google_ads') {
            return $this->googleAdsService->getDashboardData();
        }

        return $this->metaService->getDashboardData();
    }

    // Lấy thống kê khách hàng
    protected function getCustomerSummary(): array
    {
        $result = $this->userService->getCustomerSummaryForDashboard();

        if ($result->isSuccess()) {
            return $result->getData();
        }

        // Nếu lỗi, trả về giá trị mặc định
        return [
            'total_customers' => 0,
            'active_customers' => 0,
        ];
    }

    // Lấy danh sách giao dịch chờ duyệt
    protected function getPendingTransactions(Request $request): array
    {
        // Lấy số trang từ request
        $page = max(1, (int) $request->input('pending_page', 1));

        // Tạo query để lấy danh sách
        $query = new QueryListDTO(
            perPage: 20,
            page: $page,
            filter: [],
            sortBy: 'created_at',
            sortDirection: 'desc',
        );

        // Gọi service để lấy data
        $result = $this->walletTransactionService->getPendingTransactionsPaginated($query);

        // Xử lý kết quả
        $paginator = $result->isSuccess() ? $result->getData() : null;
        $error = $result->isError() ? $result->getMessage() : null;

        $paginationPayload = null;
        if ($paginator) {
            $paginationPayload = [
                'data' => $paginator->items(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'next' => $paginator->nextPageUrl(),
                    'prev' => $paginator->previousPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ];
        }

        return [
            'total' => $paginator ? $paginator->total() : 0,
            'pagination' => $paginationPayload,
            'error' => $error,
        ];
    }

    protected function isAgencyOrCustomer($user): bool
    {
        return in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value]);
    }

    protected function isAdminOrStaff($user): bool
    {
        return in_array($user->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ]);
    }
}
