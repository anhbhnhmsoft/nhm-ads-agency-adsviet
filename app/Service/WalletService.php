<?php

namespace App\Service;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\User;
use App\Repositories\WalletRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Common\Constants\Wallet\WalletStatus;
use App\Common\Constants\User\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WalletService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserWalletTransactionRepository $transactionRepository,
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
                'password' => $password ? Hash::make($password) : null,
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
        $wallet->update(['password' => Hash::make($newPassword)]);
        return ServiceReturn::success();
    }

    /**
     * User tự đổi mật khẩu ví của chính mình
     * Nếu ví chưa có mật khẩu, không cần current_password
     * Nếu ví đã có mật khẩu, cần verify current_password
     */
    public function changePassword(int $userId, ?string $currentPassword, string $newPassword): ServiceReturn
    {
        $actor = Auth::user();
        if (!$actor) {
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }
        
        // Chỉ cho phép user đổi mật khẩu ví của chính mình
        if ((int) $actor->id !== (int) $userId) {
            Logging::web('WalletService@changePassword permission denied', [
                'actor_id' => $actor->id,
                'target_id' => $userId,
            ]);
            return ServiceReturn::error(message: __('common_error.permission_denied'));
        }

        $wallet = $this->walletRepository->findByUserId($userId);
        if (!$wallet) {
            $createResult = $this->createForUser($userId);
            if ($createResult->isError()) {
                return $createResult;
            }
            $wallet = $createResult->getData();
        }

        // Nếu ví đã có mật khẩu, cần verify current password
        if (!empty($wallet->password)) {
            if (!$currentPassword || !Hash::check($currentPassword, $wallet->password)) {
                Logging::web('WalletService@changePassword invalid current password', [
                    'user_id' => $userId,
                ]);
                return ServiceReturn::error(message: __('Mật khẩu ví hiện tại không chính xác'));
            }
        }

        $wallet->update(['password' => Hash::make($newPassword)]);
        Logging::web('WalletService@changePassword success', [
            'user_id' => $userId,
        ]);
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
            if (!$walletPassword || !Hash::check($walletPassword, $wallet->password)) {
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
            $targetId = (int) $targetUserId;
            $actor = Auth::user();
            if (!$actor) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }
            // Kiểm tra quyền xem thông tin ví
            $canView = $this->canPerformAction($actor, $targetId);

            if (!$canView) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $result = $this->findByUserId($targetId);
            // Nếu wallet chưa tồn tại, tự động tạo ví mới
            if (!$result->isSuccess()) {
                $createResult = $this->createForUser($targetId);
                if (!$createResult->isSuccess()) {
                    return $createResult;
                }
                $wallet = $createResult->getData();
            } else {
                $wallet = $result->getData();
            }

            $transactions = $wallet->transactions()
                ->with('wallet.user')
                ->latest('created_at')
                ->limit(20)
                ->get();

            return ServiceReturn::success(data: [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => number_format($wallet->balance, 2, '.', ''),
                'status' => $wallet->status,
                'has_password' => !empty($wallet->password),
                'transactions' => $transactions,
            ]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletService@getWalletForUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function getTransactionsForUser(int $userId, QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->findByUserId($userId);
            if (!$wallet) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            $paginator = $this->transactionRepository
                ->filterForWallet($wallet->id, $queryListDTO->filter ?? [])
                ->orderBy($queryListDTO->sortBy ?? 'created_at', $queryListDTO->sortDirection ?? 'desc')
                ->paginate(
                    $queryListDTO->perPage,
                    ['*'],
                    'page',
                    $queryListDTO->page
                );

            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletService@getTransactionsForUser error: '.$e->getMessage(),
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
            return (int) $actor->id === $targetUserId;
        }
        if ($role === UserRole::EMPLOYEE->value || $role === UserRole::MANAGER->value) {
            return $this->userReferralRepository
                ->query()
                ->where('referrer_id', (int) $actor->id)
                ->where('referred_id', $targetUserId)
                ->exists();
        }
        return false;
    }

    public function canViewWallet(User $actor, int $targetUserId): bool
    {
        return $this->canPerformAction($actor, $targetUserId);
    }

    // Lấy danh sách wallet IDs của các user được quản lý bởi referrer
    public function getWalletIdsForManagedUsers(int $referrerId): array
    {
        $walletIds = [];

        // Lấy wallet của chính referrer
        $ownWallet = $this->walletRepository->findByUserId($referrerId);
        if ($ownWallet) {
            $walletIds[] = $ownWallet->id;
        }

        // Lấy wallet của các user được quản lý
        $managedUserIds = $this->userReferralRepository->query()
            ->where('referrer_id', $referrerId)
            ->pluck('referred_id')
            ->toArray();

        if (!empty($managedUserIds)) {
            $managedWallets = $this->walletRepository->query()
                ->whereIn('user_id', $managedUserIds)
                ->pluck('id')
                ->toArray();
            $walletIds = array_merge($walletIds, $managedWallets);
        }

        return $walletIds;
    }

    // Lấy wallet ID theo user ID

    public function getWalletIdByUserId(int $userId): ?int
    {
        $wallet = $this->walletRepository->findByUserId($userId);
        return $wallet ? $wallet->id : null;
    }
}