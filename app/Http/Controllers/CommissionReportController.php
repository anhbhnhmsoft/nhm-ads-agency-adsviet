<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Resources\CommissionTransactionResource;
use App\Service\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CommissionReportController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService,
    ) {
    }

    /**
     * Hiển thị báo cáo hoa hồng (theo nhân viên / quản lý)
     */
    public function index(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);

        $result = $this->commissionService->getCommissionReport(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        $summaryResult = $this->commissionService->getCommissionSummaryByEmployee($params->get('filter') ?? []);

        return $this->rendering(
            view: 'commission/report',
            data: [
                'paginator' => fn () => CommissionTransactionResource::collection($result->getData()),
                'summary_by_employee' => fn () => $summaryResult->isSuccess() ? $summaryResult->getData() : [],
            ]
        );
    }

    /**
     * Chốt lương (đánh dấu các hoa hồng đã thanh toán)
     */
    public function markAsPaid(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }

        $paidAt = $request->input('paid_at');

        $result = $this->commissionService->markCommissionsAsPaid($ids, $paidAt);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return back()->with('success', __('commission.report_mark_paid_success'));
    }
}



