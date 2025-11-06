<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\UserWallet;
use App\Models\User;
use App\Repositories\WalletRepository;
use App\Repositories\UserReferralRepository;
use App\Common\Constants\Wallet\WalletStatus;
use App\Common\Constants\User\UserRole;
use Illuminate\Support\Facades\Auth;

class WalletService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected UserReferralRepository $userReferralRepository,
    ) {
    }

    public function createForUser(int $userId, ?string $password = null): ServiceReturn
    {
        try {
            $exists = $this->walletRepository->findByUserId($userId);
            if ($exists) {
                return ServiceReturn::success(data: $exists);
            }
            $wallet = $this->walletRepository->create([
                'user_id' => $userId,
                'balance' => 0,
                'password' => $password ? password_hash($password, PASSWORD_BCRYPT) : null,
                'status' => WalletStatus::ACTIVE->value,
            ]);
            return ServiceReturn::success(data: $wallet);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletService@createForUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function create(int $userId, ?string $password = null): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        return $this->createForUser($userId, $password);
    }

    public function findByUserId(int $userId): ServiceReturn
    {
        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        return ServiceReturn::success(data: $wallet);
    }

    public function lock(int $userId): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) return ServiceReturn::error(message: __('common_error.data_not_found'));
        $wallet->update(['status' => WalletStatus::LOCKED->value]);
        return ServiceReturn::success();
    }

    public function unlock(int $userId): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) return ServiceReturn::error(message: __('common_error.data_not_found'));
        $wallet->update(['status' => WalletStatus::ACTIVE->value]);
        return ServiceReturn::success();
    }

    public function resetPassword(int $userId, string $newPassword): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) return ServiceReturn::error(message: __('common_error.data_not_found'));
        $wallet->update(['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        return ServiceReturn::success();
    }

    public function topUp(int $userId, float $amount): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        if ($amount <= 0) {
            return ServiceReturn::error(message: __('Số tiền nạp không hợp lệ'));
        }
        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) return ServiceReturn::error(message: __('common_error.data_not_found'));
        if ($wallet->status === WalletStatus::LOCKED->value) return ServiceReturn::error(message: __('Ví đang bị khóa'));
        $wallet->update(['balance' => (float)$wallet->balance + $amount]);
        return ServiceReturn::success();
    }

    public function withdraw(int $userId, float $amount, ?string $walletPassword = null): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        if (!$this->canPerformAction($actor, $userId)) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        if ($amount <= 0) {
            return ServiceReturn::error(message: __('Số tiền rút không hợp lệ'));
        }
        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) return ServiceReturn::error(message: __('common_error.data_not_found'));
        if ($wallet->status === WalletStatus::LOCKED->value) return ServiceReturn::error(message: __('Ví đang bị khóa'));
        // Kiểm tra mật khẩu ví nếu có đặt
        if (!empty($wallet->password)) {
            if (!$walletPassword || !password_verify($walletPassword, $wallet->password)) {
                return ServiceReturn::error(message: __('Mật khẩu ví không chính xác'));
            }
        }
        if ((float)$wallet->balance < $amount) {
            return ServiceReturn::error(message: __('Số dư không đủ'));
        }
        $wallet->update(['balance' => (float)$wallet->balance - $amount]);
        
        return ServiceReturn::success();
    }

    public function getWalletForUser(int $targetUserId): ServiceReturn
    {
        try {
            $actor = Auth::user();
            if (!$actor) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }
            // Kiểm tra quyền xem thông tin ví
            $canView = $this->canPerformAction($actor, $targetUserId);

            if (!$canView) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $result = $this->findByUserId($targetUserId);
            if (!$result->isSuccess()) {
                return $result;
            }

            $wallet = $result->getData();
            return ServiceReturn::success(data: [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => number_format($wallet->balance, 2, '.', ''),
                'status' => $wallet->status,
            ]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletService@getWalletForUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function canPerformAction(User $actor, int $targetUserId): bool
    {
        $role = $actor->role;
        if ($role === UserRole::ADMIN->value) {
            return true;
        }
        if ($role === UserRole::CUSTOMER->value || $role === UserRole::AGENCY->value) {
            return $actor->id === $targetUserId;
        }
        if ($role === UserRole::EMPLOYEE->value || $role === UserRole::MANAGER->value) {
            return $this->userReferralRepository
                ->query()
                ->where('referrer_id', $actor->id)
                ->where('referred_id', $targetUserId)
                ->exists();
        }
        return false;
    }
}


