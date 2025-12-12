<?php

namespace App\Http\Controllers;

use App\Common\Helper;
use App\Core\Controller;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpendReportController extends Controller
{
    public function __construct(
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
    ) {
    }

    public function index(Request $request): Response
    {
        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());
        $datePreset = $request->string('date_preset', 'last_7d')->toString();

        // Validate date_preset
        $validPresets = ['last_7d', 'last_14d', 'last_28d', 'last_30d', 'last_90d'];
        if (!in_array($datePreset, $validPresets)) {
            $datePreset = 'last_7d';
        }

        $reportData = null;
        $insightData = null;
        $error = null;

        try {
            if ($platform === 'google_ads') {
                $reportResult = $this->googleAdsService->getReportData();
                $insightResult = $this->googleAdsService->getReportInsights($datePreset);
            } else {
                $reportResult = $this->metaService->getReportData();
                $insightResult = $this->metaService->getReportInsights($datePreset);
            }

            if ($reportResult->isSuccess()) {
                $reportData = $reportResult->getData();
            } else {
                $error = $reportResult->getMessage();
            }

            if ($insightResult->isSuccess()) {
                $insightData = $insightResult->getData();
            }
        } catch (\Throwable $e) {
            $error = __('common_error.server_error');
        }

        return Inertia::render('spend-report/index', [
            'reportData' => $reportData,
            'insightData' => $insightData,
            'selectedPlatform' => $platform,
            'selectedDatePreset' => $datePreset,
            'error' => $error,
        ]);
    }

    /**
     * Bảng xếp hạng chi tiêu các tài khoản
     */
    public function ranking(Request $request): Response
    {
        $platform = Helper::getValidatedPlatform($request->string('platform', 'meta')->toString());
        
        // Lấy date range từ request
        $startDate = null;
        $endDate = null;
        
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startDateStr = $request->string('start_date')->toString();
                $endDateStr = $request->string('end_date')->toString();
                
                if (!empty($startDateStr) && !empty($endDateStr)) {
                    $startDate = \Carbon\Carbon::parse($startDateStr)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($endDateStr)->endOfDay();
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

        $rankingData = null;
        $error = null;

        try {
            if ($platform === 'google_ads') {
                $result = $this->googleAdsService->getAccountSpendingRanking($startDate, $endDate);
            } else {
                $result = $this->metaService->getAccountSpendingRanking($startDate, $endDate);
            }

            if ($result->isSuccess()) {
                $rankingData = $result->getData();
            } else {
                $error = $result->getMessage();
            }
        } catch (\Throwable $e) {
            $error = __('common_error.server_error');
        }

        return Inertia::render('spend-report/ranking', [
            'rankingData' => $rankingData,
            'selectedPlatform' => $platform,
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
            'error' => $error,
        ]);
    }
}

