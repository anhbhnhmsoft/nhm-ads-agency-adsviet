<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Repositories\NotificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * Lấy danh sách thông báo của user hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $params = $this->extractQueryPagination($request);
        $perPage = $params->get('per_page', 15);
        $page = $params->get('page', 1);

        $notifications = $this->notificationRepository->query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return RestResponse::success(data: [
            'data' => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'from' => $notifications->firstItem(),
            'to' => $notifications->lastItem(),
            'unread_count' => $this->notificationRepository->query()
                ->where('user_id', $user->id)
                ->where('status', 0)
                ->count(),
        ]);
    }

    /**
     * Đánh dấu thông báo là đã đọc
     */
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $notification = $this->notificationRepository->query()
            ->where('id', (string) $id)
            ->where('user_id', (string) $user->id)
            ->first();

        if (!$notification) {
            return RestResponse::error(message: __('common_error.not_found'), status: 404);
        }

        $notification->update(['status' => 1]);

        return RestResponse::success(
            data: $notification,
            message: __('common.success')
        );
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $this->notificationRepository->query()
            ->where('user_id', $user->id)
            ->where('status', 0)
            ->update(['status' => 1]);

        return RestResponse::success(message: __('common.success'));
    }
}

