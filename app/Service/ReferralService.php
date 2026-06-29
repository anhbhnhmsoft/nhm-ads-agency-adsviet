<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\User;
use App\Repositories\CommissionTransactionRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;

class ReferralService
{
    public function __construct(
        protected UserReferralRepository $userReferralRepository,
        protected CommissionTransactionRepository $commissionTransactionRepository,
        protected UserRepository $userRepository,
    ) {
    }

    /**
     * Lấy thông tin referral của user hiện tại
     */
    public function getReferralInfo(string $userId): ServiceReturn
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            // Đếm số người đã mời
            $downlineCount = $this->userReferralRepository->getDownlineCount($userId);

            // Tính tổng hoa hồng nhận được
            $totalCommission = $this->commissionTransactionRepository->query()
                ->where('referrer_id', $userId)
                ->sum('commission_amount');

            return ServiceReturn::success(data: [
                'referral_code' => $user->referral_code,
                'total_downline' => $downlineCount,
                'total_commission' => (float) $totalCommission,
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'ReferralService@getReferralInfo error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách người đã mời (downline)
     */
    public function getDownline(string $userId, int $page = 1, int $perPage = 20): ServiceReturn
    {
        try {
            $downlineIds = $this->userReferralRepository->getDownlineIds($userId);

            if (empty($downlineIds)) {
                return ServiceReturn::success(data: [
                    'data' => [],
                    'meta' => [
                        'current_page' => $page,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                    ],
                ]);
            }

            $paginator = $this->userRepository->query()
                ->whereIn('id', $downlineIds)
                ->select('id', 'name', 'username', 'email', 'role', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return ServiceReturn::success(data: [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'ReferralService@getDownline error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy lịch sử hoa hồng của user
     */
    public function getCommissions(string $userId, int $page = 1, int $perPage = 20): ServiceReturn
    {
        try {
            $paginator = $this->commissionTransactionRepository->query()
                ->where('referrer_id', $userId)
                ->with(['customer:id,name,username'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return ServiceReturn::success(data: [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'ReferralService@getCommissions error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
