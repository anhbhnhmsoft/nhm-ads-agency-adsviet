<?php

namespace App\Http\Controllers;

use App\Common\Helper;
use App\Core\Controller;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
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
}

