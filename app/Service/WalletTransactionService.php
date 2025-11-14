<?php

namespace App\Service;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\UserWallet;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletTransactionService
{
    public function __construct(
        protected UserWalletTransactionRepository $transactionRepository,
        protected WalletRepository $walletRepository,
    ) {
    }

    // Tạo lệnh nạp tiền (DEPOSIT) cho user
    public function createDepositOrder(int $userId, float $amount, string $network, string $depositAddress, ?string $customerName = null, ?string $customerEmail = null, ?string $paymentId = null, ?string $payAddress = null, ?\DateTime $expiresAt = null): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->findByUserId($userId);
            if (!$wallet) {
                return ServiceReturn::error(message: __('Ví không tồn tại'));
            }

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => WalletTransactionType::DEPOSIT->value,
                'status' => WalletTransactionStatus::PENDING->value,
                'description' => 'User tạo lệnh nạp tiền',
                'network' => $network,
                'deposit_address' => $depositAddress,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'payment_id' => $paymentId,
                'pay_address' => $payAddress,
                'expires_at' => $expiresAt,
            ]);

            return ServiceReturn::success(data: $transaction);
        } catch (QueryException $e) {
            Logging::error('WalletTransactionService@createDepositOrder error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    //Tìm giao dịch theo payment_id của NowPayments
    public function findByPaymentId(string $paymentId): ServiceReturn
    {
        try {
            $transaction = $this->transactionRepository->query()->where('payment_id', $paymentId)->first();
            if (!$transaction) {
                return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
            }
            return ServiceReturn::success(data: $transaction);
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@findByPaymentId error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Duyệt lệnh nạp (DEPOSIT) thành công và cập nhập số dư cho user
    public function approveDeposit(int $transactionId, ?string $txHash = null): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($transactionId, $txHash) {
                $transaction = $this->transactionRepository->query()->find($transactionId);
                if (!$transaction) {
                    return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
                }
                if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                    return ServiceReturn::error(message: __('Giao dịch không ở trạng thái chờ'));
                }

                // Cập nhập số dư cho user
                $wallet = $this->walletRepository->query()->find($transaction->wallet_id);
                if (!$wallet) {
                    return ServiceReturn::error(message: __('Ví không tồn tại'));
                }
                $newBalance = (float) $wallet->balance + (float) $transaction->amount;
                $this->walletRepository->query()->where('id', $wallet->id)->update(['balance' => $newBalance]);

                // Cập nhật trạng thái giao dịch thành APPROVED
                $this->transactionRepository->updateById($transactionId, [
                    'status' => WalletTransactionStatus::APPROVED->value,
                    'tx_hash' => $txHash,
                    'description' => 'Admin duyệt nạp tiền',
                ]);

                return ServiceReturn::success();
            });
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@approveDeposit error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Cập nhật trạng thái giao dịch
    public function updateTransactionStatus(int $transactionId, int $status, ?string $description = null): ServiceReturn
    {
        try {
            $updateData = ['status' => $status];
            if ($description !== null) {
                $updateData['description'] = $description;
            }
            $this->transactionRepository->updateById($transactionId, $updateData);
            return ServiceReturn::success();
        } catch (QueryException $e) {
            Logging::error('WalletTransactionService@updateTransactionStatus error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy lệnh nạp đang chờ xử lý của wallet (chưa hết hạn)
    // Trả về null nếu không có hoặc đã hết hạn
    public function getPendingDepositForWallet(int $walletId): ?array
    {
        try {
            $pendingTx = $this->transactionRepository->query()
                ->where('wallet_id', $walletId)
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->where('type', WalletTransactionType::DEPOSIT->value)
                ->whereNotNull('expires_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$pendingTx || !$pendingTx->expires_at) {
                return null;
            }

            $expiresAt = $pendingTx->expires_at;
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return null;
            }

            return [
                'id' => $pendingTx->id,
                'amount' => (float) $pendingTx->amount,
                'network' => $pendingTx->network,
                'deposit_address' => $pendingTx->deposit_address,
                'payment_id' => $pendingTx->payment_id,
                'pay_address' => $pendingTx->pay_address,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in' => max($expiresAt->diffInSeconds(now()), 0),
            ];
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@getPendingDepositForWallet error: '.$e->getMessage(), exception: $e);
            return null;
        }
    }


    // Chỉ cho phép hủy nếu transaction thuộc về wallet của user và đang ở trạng thái PENDING
    public function cancelDepositByUser(int $transactionId, int $userId): ServiceReturn
    {
        try {
            $transaction = $this->transactionRepository->query()->find($transactionId);
            if (!$transaction) {
                return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
            }

            // Kiểm tra wallet thuộc về user
            $wallet = $this->walletRepository->query()->find($transaction->wallet_id);
            if (!$wallet || (int)$wallet->user_id !== $userId) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Kiểm tra trạng thái PENDING
            if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                return ServiceReturn::error(message: __('Giao dịch không ở trạng thái chờ'));
            }

            // Cập nhật trạng thái thành CANCELLED
            $this->transactionRepository->updateById($transactionId, [
                'status' => WalletTransactionStatus::CANCELLED->value,
                'description' => 'User hủy lệnh nạp từ màn hình chờ',
            ]);

            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@cancelDepositByUser error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy danh sách giao dịch với lọc và phân trang
    public function getTransactionsWithFilters(QueryListDTO $queryListDTO, array $filters = []): ServiceReturn
    {
        try {
            $query = $this->transactionRepository->query()
                ->with('wallet.user')
                ->latest('created_at');

            // Lọc theo wallet_ids
            if (!empty($filters['wallet_ids'])) {
                $query->whereIn('wallet_id', $filters['wallet_ids']);
            }

            // Lọc theo type
            if (!empty($filters['type'])) {
                $typeValue = (int) $filters['type'];
                $validTypeValues = array_column(WalletTransactionType::cases(), 'value');
                if (in_array($typeValue, $validTypeValues, true)) {
                    $query->where('type', $typeValue);
                }
            }

            // Lọc theo status
            if (!empty($filters['status'])) {
                $statusValue = (int) $filters['status'];
                $validStatusValues = array_column(WalletTransactionStatus::cases(), 'value');
                if (in_array($statusValue, $validStatusValues, true)) {
                    $query->where('status', $statusValue);
                }
            }

            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách giao dịch WalletTransactionService@getTransactionsWithFilters: ' . $exception->getMessage(),
                exception: $exception
            );
            // Trả về paginator rỗng khi có lỗi
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $queryListDTO->perPage,
                    currentPage: $queryListDTO->page
                )
            );
        }
    }

    // Tìm transaction by ID
    public function findById(int $id)
    {
        return $this->transactionRepository->query()->find($id);
    }

}


