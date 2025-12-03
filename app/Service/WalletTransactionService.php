<?php

namespace App\Service;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Service\UserAlertService;
use App\Service\MailService;
use App\Models\User;
use App\Models\UserWalletTransaction;

class WalletTransactionService
{
    public function __construct(
        protected UserWalletTransactionRepository $transactionRepository,
        protected WalletRepository $walletRepository,
        protected UserAlertService $userAlertService,
        protected MailService $mailService,
        protected UserRepository $userRepository,
    ) {
    }

    /**
     * Gửi thông báo cho user khi có giao dịch ví mới được tạo
     */
    private function notifyTransaction(UserWalletTransaction $transaction, ?string $userId = null): void
    {
        try {
            $transaction->loadMissing('wallet.user');
            $wallet = $transaction->wallet;
            $user = $wallet?->user;

            if (!$user) {
                return;
            }

            $amount = (float) $transaction->amount;
            $amountFormatted = number_format($amount, 2);

            $typeOptions = WalletTransactionType::getOptions();
            $typeLabel = $typeOptions[$transaction->type] ?? $typeOptions[WalletTransactionType::UNKNOWN->value];

            $sign = $amount >= 0 ? '+' : '';
            $lines = [
                __('wallet.notifications.title', ['type' => $typeLabel]),
                __('wallet.notifications.amount', ['amount' => $sign.$amountFormatted]),
            ];
            $description = (string) ($transaction->description ?? '');
            if ($description !== '') {
                $lines[] = __('wallet.notifications.description', ['description' => $description]);
            }
            $message = implode("\n", $lines);
            $description = (string) ($transaction->description ?? '');

            $this->userAlertService->sendPlainText(
                $user,
                $message,
                function (MailService $mailService, User $u) use ($typeLabel, $amount, $description) {
                    return $mailService->sendWalletTransactionAlert(
                        email: $u->email,
                        username: $u->name ?? $u->username,
                        typeLabel: $typeLabel,
                        amount: $amount,
                        description: $description ?: null,
                    );
                }
            );
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletTransactionService@notifyTransaction error: '.$e->getMessage(),
                exception: $e
            );
        }
    }

    private function notifyAdminsDeposit(UserWalletTransaction $transaction, string $stage): void
    {
        try {
            $transaction->loadMissing('wallet.user');
            $customer = $transaction->wallet?->user;
            if (!$customer) {
                return;
            }

            $admins = $this->userRepository->getActiveAdminsWithEmail();
            if ($admins->isEmpty()) {
                return;
            }

            $typeLabel = __('wallet.transaction_type.deposit');
            $amount = abs((float) $transaction->amount);
            $description = $transaction->description ?? null;

            foreach ($admins as $admin) {
                $this->mailService->sendAdminWalletTransactionAlert(
                    email: $admin->email,
                    adminName: $admin->name ?? $admin->username,
                    customerName: $customer->name ?? $customer->username,
                    typeLabel: $typeLabel,
                    amount: $amount,
                    stage: $stage,
                    description: $description
                );
            }
        } catch (\Throwable $e) {
            Logging::error(
                message: 'WalletTransactionService@notifyAdminsDeposit error: '.$e->getMessage(),
                exception: $e
            );
        }
    }

    // Tạo lệnh nạp tiền (DEPOSIT) cho user
    public function createDepositOrder(int $userId, float $amount, string $network, string $depositAddress, ?string $customerName = null, ?string $customerEmail = null, ?string $paymentId = null, ?string $payAddress = null, ?\DateTime $expiresAt = null): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->findByUserId($userId);
            if (!$wallet) {
                return ServiceReturn::error(message: __('wallet.error.wallet_not_found'));
            }

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => WalletTransactionType::DEPOSIT->value,
                'status' => WalletTransactionStatus::PENDING->value,
                'description' => __('wallet.transaction_description.deposit_created'),
                'network' => $network,
                'deposit_address' => $depositAddress,
                'customer_name' => $customerName,
                'payment_id' => $paymentId,
                'pay_address' => $payAddress,
                'expires_at' => $expiresAt,
            ]);

            // Gửi thông báo cho user & admin
            $this->notifyTransaction($transaction, $wallet->user?->id);
            $this->notifyAdminsDeposit($transaction, 'created');

            return ServiceReturn::success(data: $transaction);
        } catch (QueryException $e) {
            Logging::error('WalletTransactionService@createDepositOrder error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Tạo lệnh rút tiền (WITHDRAW) cho user và trừ tiền ngay
    public function createWithdrawOrder(int $userId, float $amount, array $withdrawInfo, ?string $walletPassword = null): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($userId, $amount, $withdrawInfo, $walletPassword) {
                $wallet = $this->walletRepository->findByUserId($userId);
                if (!$wallet) {
                    return ServiceReturn::error(message: __('wallet.error.wallet_not_found'));
                }

                // Kiểm tra mật khẩu ví nếu có đặt
                if (!empty($wallet->password)) {
                    if (empty($walletPassword) || !Hash::check($walletPassword, $wallet->password)) {
                        return ServiceReturn::error(message: __('wallet.error.wallet_password_invalid'));
                    }
                }

                // Kiểm tra số dư
                if ((float) $wallet->balance < $amount) {
                    return ServiceReturn::error(message: __('wallet.error.wallet_balance_not_enough'));
                }

                // Trừ tiền ngay khi tạo lệnh
                $newBalance = (float) $wallet->balance - $amount;
                $this->walletRepository->query()->where('id', $wallet->id)->update(['balance' => $newBalance]);

                // Tạo transaction với status PENDING
                $transaction = $this->transactionRepository->create([
                    'wallet_id' => $wallet->id,
                    'amount' => -$amount,
                    'type' => WalletTransactionType::WITHDRAW->value,
                    'status' => WalletTransactionStatus::PENDING->value,
                    'description' => __('wallet.transaction_description.withdraw_created'),
                    'withdraw_info' => $withdrawInfo,
                ]);

                // Gửi thông báo cho user
                $this->notifyTransaction($transaction, $wallet->user?->id);

                return ServiceReturn::success(data: $transaction);
            });
        } catch (QueryException $e) {
            Logging::error('WalletTransactionService@createWithdrawOrder error: '.$e->getMessage(), exception: $e);
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
            $result = DB::transaction(function () use ($transactionId, $txHash) {
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
                    return ServiceReturn::error(message: __('wallet.error.wallet_not_found'));
                }
                $newBalance = (float) $wallet->balance + (float) $transaction->amount;
                $this->walletRepository->query()->where('id', $wallet->id)->update(['balance' => $newBalance]);

                // Cập nhật trạng thái giao dịch thành APPROVED
                $this->transactionRepository->updateById($transactionId, [
                    'status' => WalletTransactionStatus::APPROVED->value,
                    'tx_hash' => $txHash,
                    'description' => __('wallet.transaction_description.deposit_approved'),
                ]);

                $transaction->status = WalletTransactionStatus::APPROVED->value;
                $transaction->tx_hash = $txHash;

                return ServiceReturn::success(data: $transaction);
            });

            if ($result->isSuccess()) {
                $transaction = $result->getData();
                if ($transaction instanceof UserWalletTransaction) {
                    $this->notifyTransaction($transaction, $transaction->wallet?->user?->id);
                    $this->notifyAdminsDeposit($transaction, 'approved');
                }
                return ServiceReturn::success();
            }

            return $result;
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@approveDeposit error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Duyệt lệnh rút tiền (WITHDRAW) - chỉ cập nhật status, không cần trừ tiền vì đã trừ khi tạo lệnh
    public function approveWithdraw(int $transactionId, ?string $txHash = null): ServiceReturn
    {
        try {
            $transaction = $this->transactionRepository->query()->find($transactionId);
            if (!$transaction) {
                return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
            }

            // Kiểm tra là lệnh rút tiền
            if ((int) $transaction->type !== WalletTransactionType::WITHDRAW->value) {
                return ServiceReturn::error(message: __('Không phải lệnh rút tiền'));
            }

            if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                return ServiceReturn::error(message: __('Giao dịch không ở trạng thái chờ'));
            }

            // Cập nhật trạng thái giao dịch thành COMPLETED (đã hoàn thành chuyển tiền)
            $this->transactionRepository->updateById($transactionId, [
                'status' => WalletTransactionStatus::COMPLETED->value,
                'tx_hash' => $txHash,
                'description' => __('wallet.transaction_description.withdraw_completed'),
            ]);

            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@approveWithdraw error: '.$e->getMessage(), exception: $e);
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

    public function getPendingTransactionsSummary(int $limit = 10): ServiceReturn
    {
        try {
            $baseQuery = $this->transactionRepository->query()
                ->with(['wallet.user'])
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->orderByDesc('created_at');

            $pendingCount = (clone $baseQuery)->count();

            $typeLabels = WalletTransactionType::getOptions();
            $statusLabels = WalletTransactionStatus::getOptions();

            $transactions = (clone $baseQuery)
                ->limit($limit)
                ->get()
                ->map(function ($transaction) use ($typeLabels, $statusLabels) {
                    $walletUser = $transaction->wallet?->user;

                    return [
                        'id' => (string) $transaction->id,
                        'amount' => (float) $transaction->amount,
                        'type' => $transaction->type,
                        'type_label' => $typeLabels[$transaction->type] ?? $typeLabels[WalletTransactionType::UNKNOWN->value],
                        'status' => $transaction->status,
                        'status_label' => $statusLabels[$transaction->status] ?? $statusLabels[WalletTransactionStatus::UNKNOWN->value],
                        'customer_id' => $walletUser?->id,
                        'customer_name' => $transaction->customer_name ?? $walletUser?->name,
                        'customer_email' => $transaction->customer_email ?? $walletUser?->username,
                        'description' => $transaction->description,
                        'network' => $transaction->network,
                        'created_at' => optional($transaction->created_at)?->toIso8601String(),
                        'withdraw_info' => $transaction->withdraw_info,
                    ];
                })
                ->values()
                ->toArray();

            return ServiceReturn::success(data: [
                'pending_transactions' => $pendingCount,
                'pending_transactions_list' => $transactions,
            ]);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'WalletTransactionService@getPendingTransactionsSummary error: '.$exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function getPendingTransactionsPaginated(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $query = $this->transactionRepository->query()
                ->with(['wallet.user'])
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->orderByDesc('created_at');

            $typeLabels = WalletTransactionType::getOptions();
            $statusLabels = WalletTransactionStatus::getOptions();

            $paginator = $query
                ->paginate(
                    $queryListDTO->perPage,
                    ['*'],
                    'page',
                    $queryListDTO->page
                )
                ->through(function ($transaction) use ($typeLabels, $statusLabels) {
                    $walletUser = $transaction->wallet?->user;

                    return [
                        'id' => (string) $transaction->id,
                        'amount' => (float) $transaction->amount,
                        'type' => $transaction->type,
                        'type_label' => $typeLabels[$transaction->type] ?? $typeLabels[WalletTransactionType::UNKNOWN->value],
                        'status' => $transaction->status,
                        'status_label' => $statusLabels[$transaction->status] ?? $statusLabels[WalletTransactionStatus::UNKNOWN->value],
                        'customer_id' => $walletUser?->id,
                        'customer_name' => $transaction->customer_name ?? $walletUser?->name,
                        'customer_email' => $transaction->customer_email ?? $walletUser?->username,
                        'description' => $transaction->description,
                        'network' => $transaction->network,
                        'created_at' => optional($transaction->created_at)?->toIso8601String(),
                        'withdraw_info' => $transaction->withdraw_info,
                    ];
                });

            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'WalletTransactionService@getPendingTransactionsPaginated error: '.$exception->getMessage(),
                exception: $exception
            );
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
                'id' => (string) $pendingTx->id,
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
    public function cancelDepositByUser(string|int $transactionId, int $userId): ServiceReturn
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
                'description' => __('wallet.transaction_description.deposit_cancelled_user'),
            ]);

            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@cancelDepositByUser error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Hủy lệnh rút tiền và hoàn lại tiền cho user
    public function cancelWithdrawByUser(string|int $transactionId, int $userId): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($transactionId, $userId) {
                $transaction = $this->transactionRepository->query()->find($transactionId);
                if (!$transaction) {
                    return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
                }

                // Kiểm tra wallet thuộc về user
                $wallet = $this->walletRepository->query()->find($transaction->wallet_id);
                if (!$wallet || (int)$wallet->user_id !== $userId) {
                    return ServiceReturn::error(message: __('common_error.permission_denied'));
                }

                // Kiểm tra là lệnh rút tiền
                if ((int) $transaction->type !== WalletTransactionType::WITHDRAW->value) {
                    return ServiceReturn::error(message: __('Không phải lệnh rút tiền'));
                }

                // Kiểm tra trạng thái PENDING
                if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                    return ServiceReturn::error(message: __('Giao dịch không ở trạng thái chờ'));
                }

                // Hoàn lại tiền vào ví (amount là số âm, nên dùng abs để lấy giá trị dương)
                $refundAmount = abs((float) $transaction->amount);
                $newBalance = (float) $wallet->balance + $refundAmount;
                $this->walletRepository->query()->where('id', $wallet->id)->update(['balance' => $newBalance]);

                // Cập nhật trạng thái thành CANCELLED
                $this->transactionRepository->updateById($transactionId, [
                    'status' => WalletTransactionStatus::CANCELLED->value,
                'description' => __('wallet.transaction_description.withdraw_cancelled_user'),
                ]);

                return ServiceReturn::success();
            });
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@cancelWithdrawByUser error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Admin hủy lệnh rút tiền và hoàn lại tiền cho user
    public function cancelWithdrawByAdmin(string|int $transactionId): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($transactionId) {
                $transaction = $this->transactionRepository->query()->find($transactionId);
                if (!$transaction) {
                    return ServiceReturn::error(message: __('Giao dịch không tồn tại'));
                }

                // Kiểm tra là lệnh rút tiền
                if ((int) $transaction->type !== WalletTransactionType::WITHDRAW->value) {
                    return ServiceReturn::error(message: __('Không phải lệnh rút tiền'));
                }

                // Kiểm tra trạng thái PENDING
                if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                    return ServiceReturn::error(message: __('Giao dịch không ở trạng thái chờ'));
                }

                // Hoàn lại tiền vào ví
                $wallet = $this->walletRepository->query()->find($transaction->wallet_id);
                if ($wallet) {
                    $refundAmount = abs((float) $transaction->amount);
                    $newBalance = (float) $wallet->balance + $refundAmount;
                    $this->walletRepository->query()->where('id', $wallet->id)->update(['balance' => $newBalance]);
                }

                // Cập nhật trạng thái thành CANCELLED
                $this->transactionRepository->updateById($transactionId, [
                    'status' => WalletTransactionStatus::CANCELLED->value,
                'description' => __('wallet.transaction_description.withdraw_cancelled_admin'),
                ]);

                return ServiceReturn::success();
            });
        } catch (\Throwable $e) {
            Logging::error('WalletTransactionService@cancelWithdrawByAdmin error: '.$e->getMessage(), exception: $e);
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


    public function checkDepositStatus(string $transactionId): ServiceReturn
    {
        try {
            $user = Auth::user();
            $walletId = $user->wallet->id;
            if (empty($walletId)){
                return ServiceReturn::error(message: __('wallet.validation.wallet_need_configured'));
            }
            $transaction = $this->transactionRepository->query()
                ->where('wallet_id', $walletId)
                ->where('id', $transactionId)
                ->first();
            if (!$transaction) {
                return ServiceReturn::error(message: __('wallet.validation.transaction_invalid'));
            }

            if (now()->greaterThanOrEqualTo($transaction->expires_at)) {
                return ServiceReturn::error(message: __('wallet.validation.transaction_expired'));
            }
            // Kiểm tra là lệnh nạp tiền
            if ((int) $transaction->type !== WalletTransactionType::DEPOSIT->value) {
                return ServiceReturn::error(message: __('wallet.validation.transaction_invalid'));
            }

            // Kiểm tra trạng thái
            if (in_array((int) $transaction->status, [
                WalletTransactionStatus::REJECTED->value,
                WalletTransactionStatus::CANCELLED->value,
                WalletTransactionStatus::UNKNOWN->value,
            ])) {
                return ServiceReturn::error(message: __('wallet.validation.transaction_failed'));
            }
            // Kiểm tra trạng thái thành công
            if ($transaction->status === WalletTransactionStatus::APPROVED->value) {
                return ServiceReturn::success(data: true);
            }
            // không thì vẫn chưa được duyệt
            return ServiceReturn::success(data: false);

        }catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi kiểm tra trạng thái nạp tiền WalletTransactionService@checkDepositStatus: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}


