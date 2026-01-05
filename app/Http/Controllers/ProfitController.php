<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Service\ProfitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitController extends Controller
{
    public function __construct(
        protected ProfitService $profitService,
    ) {
    }

    /**
     * Trang thống kê lợi nhuận theo khách hàng (cho Agency)
     */
    public function byCustomer(Request $request): Response
    {
        $user = $request->user();
        
        // Chỉ agency mới được truy cập
        if (!$user || $user->role !== UserRole::AGENCY->value) {
            abort(403, __('common_error.permission_denied'));
        }

        // Lấy date range từ request
        $startDate = null;
        $endDate = null;
        
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startDateStr = $request->string('start_date')->toString();
                $endDateStr = $request->string('end_date')->toString();
                
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

        // Filter theo customer nếu có
        $customerId = $request->input('customer_id') ? (int) $request->input('customer_id') : null;

        $result = $this->profitService->getProfitByCustomer($customerId, $startDate, $endDate);

        $profitData = $result->isSuccess() ? $result->getData() : [];
        $error = $result->isError() ? $result->getMessage() : null;

        return Inertia::render('profit/by-customer', [
            'profitData' => $profitData,
            'error' => $error,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'selectedCustomerId' => $customerId,
        ]);
    }

    /**
     * Trang thống kê lợi nhuận theo nền tảng (cho Agency/Admin)
     */
    public function byPlatform(Request $request): Response
    {
        $user = $request->user();
        
        // Chỉ agency và admin mới được truy cập
        if (!$user || !in_array($user->role, [UserRole::AGENCY->value, UserRole::ADMIN->value])) {
            abort(403, __('common_error.permission_denied'));
        }

        // Lấy date range từ request
        $startDate = null;
        $endDate = null;
        
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startDateStr = $request->string('start_date')->toString();
                $endDateStr = $request->string('end_date')->toString();
                
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

        // Filter theo platform nếu có
        $platform = $request->input('platform') ? (int) $request->input('platform') : null;

        $result = $this->profitService->getProfitByPlatform($platform, $startDate, $endDate);

        $profitData = $result->isSuccess() ? $result->getData() : [];
        $error = $result->isError() ? $result->getMessage() : null;

        return Inertia::render('profit/by-platform', [
            'profitData' => $profitData,
            'error' => $error,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'selectedPlatform' => $platform,
        ]);
    }
}

