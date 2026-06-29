<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Service\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService,
    ) {
    }

    /**
     * Lấy thông tin referral của user hiện tại
     */
    public function info(): JsonResponse
    {
        $user = auth()->user();
        $result = $this->referralService->getReferralInfo($user->id);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData());
    }

    /**
     * Lấy danh sách người đã mời (downline)
     */
    public function downline(Request $request): JsonResponse
    {
        $user = auth()->user();
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $result = $this->referralService->getDownline($user->id, $page, $perPage);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData());
    }

    /**
     * Lấy lịch sử hoa hồng
     */
    public function commissions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $result = $this->referralService->getCommissions($user->id, $page, $perPage);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData());
    }
}
