<?php

namespace App\Http\Controllers;

use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Controller;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Service\DashboardService;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use App\Service\UserService;
use App\Service\WalletTransactionService;
use App\Service\ProfitService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
        protected UserService $userService,
        protected WalletTransactionService $walletTransactionService,
        protected DashboardService $dashboardService,
        protected ProfitService $profitService,
    ) {
    }

    // Hiển thị dashboard theo role của user
    public function index(Request $request)
    {
        $user = $request->user();

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

        // Lấy thống kê tài khoản Google và Meta
        $platformAccountsStatsResult = $this->dashboardService->getPlatformAccountsStats();
        $platformAccountsStats = $platformAccountsStatsResult->isSuccess() ? $platformAccountsStatsResult->getData() : null;

        // Lấy thống kê tickets đang yêu cầu xử lý
        $pendingTicketsByTypeResult = $this->dashboardService->getPendingTicketsByType();
        $pendingTicketsByType = $pendingTicketsByTypeResult->isSuccess() ? $pendingTicketsByTypeResult->getData() : null;

        // Lấy bảng xếp hạng chi tiêu
        $spendingRanking = $this->getSpendingRanking($request);

        // Lấy thống kê lợi nhuận theo nền tảng (chỉ cho Agency và Admin)
        $profitByPlatform = null;
        $user = $request->user();
        if (in_array($user->role, [\App\Common\Constants\User\UserRole::AGENCY->value, \App\Common\Constants\User\UserRole::ADMIN->value])) {
            $endDate = Carbon::today()->endOfDay();
            $startDate = $endDate->copy()->subDays(29)->startOfDay();
            $profitResult = $this->profitService->getProfitByPlatform(null, $startDate, $endDate);
            $profitByPlatform = $profitResult->isSuccess() ? $profitResult->getData() : null;
        }

        // Chuẩn bị data để trả về
        $adminDashboardData = [
            'total_customers' => $customerSummary['total_customers'],
            'active_customers' => $customerSummary['active_customers'],
            'pending_transactions' => $pendingTransactions['total'],
            'platform_accounts' => $platformAccountsStats,
            'pending_tickets_by_type' => $pendingTicketsByType,
            'spending_ranking' => $spendingRanking,
        ];

        return $this->rendering('dashboard/index', [
            'adminDashboardData' => $adminDashboardData,
            'adminPendingTransactions' => $pendingTransactions['pagination'],
            'dashboardError' => $pendingTransactions['error'],
            'profitByPlatform' => $profitByPlatform,
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
            perPage: 10,
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


    /**
     * Lấy bảng xếp hạng chi tiêu các tài khoản
     */
    protected function getSpendingRanking(Request $request): ?array
    {
        $platform = Helper::getValidatedPlatform($request->string('ranking_platform', 'meta')->toString());
        
        // Lấy date range từ request
        $startDate = null;
        $endDate = null;
        
        if ($request->has('ranking_start_date') && $request->has('ranking_end_date')) {
            try {
                $startDateStr = $request->string('ranking_start_date')->toString();
                $endDateStr = $request->string('ranking_end_date')->toString();
                
                if (!empty($startDateStr) && !empty($endDateStr)) {
                    $startDate = Carbon::parse($startDateStr)->startOfDay();
                    $endDate = Carbon::parse($endDateStr)->endOfDay();
                }
            } catch (\Exception $e) {
                // Nếu parse lỗi, sẽ lấy tất cả dữ liệu
            }
        }
        
        // Nếu không có date range, mặc định lấy 30 ngày gần nhất
        if (!$startDate || !$endDate) {
            $endDate = Carbon::today()->endOfDay();
            $startDate = $endDate->copy()->subDays(29)->startOfDay();
        }

        try {
            if ($platform === 'google_ads') {
                $result = $this->googleAdsService->getAccountSpendingRanking($startDate, $endDate);
            } else {
                $result = $this->metaService->getAccountSpendingRanking($startDate, $endDate);
            }

            if ($result->isSuccess()) {
                return [
                    'data' => $result->getData(),
                    'platform' => $platform,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ];
            }
        } catch (\Throwable $e) {
            Logging::error(
                message: "Error get spending ranking: " . $e->getMessage(),
                exception: $e,
            );
        }

        return null;
    }
}
