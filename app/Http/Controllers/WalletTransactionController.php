<?php

namespace App\Http\Controllers;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Resources\WalletTransactionResource;
use App\Service\WalletService;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletTransactionDescription;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Service\WalletTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletTransactionController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletTransactionService $walletTransactionService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            Logging::web('WalletTransactionController@index: No user authenticated');
            return redirect()->route('login');
        }

        // Xác định wallet_ids dựa trên role
        $walletIds = null;

        // Role: Customer mới xem giao dịch của mình
        if ($user->role === UserRole::CUSTOMER->value) {
             Logging::web('WalletTransactionController@index: Entered CUSTOMER branch');
            // Tự động tạo wallet nếu chưa có
            $walletResult = $this->walletService->findByUserId($user->id);
            if (!$walletResult->isSuccess()) {
                $createResult = $this->walletService->createForUser($user->id);
                if (!$createResult->isSuccess()) {
                     Logging::web('WalletTransactionController@index: Failed to create wallet', [
                        'user_id' => $user->id,
                        'error' => $createResult->getMessage(),
                    ]);
                    return $this->rendering('transactions/index', [
                        'transactions' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => 20,
                            'total' => 0,
                        ],
                        'filters' => $request->only(['type', 'status']),
                        'canApprove' => false,
                    ]);
                }
                $wallet = $createResult->getData();
            } else {
                $wallet = $walletResult->getData();
            }
            
            $walletIds = [$wallet->id];
        }
        // Role: Employee/agency/manager xem giao dịch của user mình quản lý + giao dịch của chính mình
        elseif (in_array($user->role, [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::AGENCY->value])) {
            $walletIds = $this->walletService->getWalletIdsForManagedUsers((int) $user->id);
            
            if (empty($walletIds)) {
                Logging::web('WalletTransactionController@index: No wallets found for AGENCY/EMPLOYEE/MANAGER');
                
                return $this->rendering('transactions/index', [
                    'transactions' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 20,
                        'total' => 0,
                    ],
                    'filters' => $request->only(['type', 'status']),
                    'canApprove' => false,
                ]);
            }
        }
        // Admin: xem tất cả, có thể filter theo user_id
        elseif ($user->role === UserRole::ADMIN->value) {
            if ($request->has('user_id') && $request->user_id) {
                $walletId = $this->walletService->getWalletIdByUserId((int) $request->user_id);
                if ($walletId) {
                    $walletIds = [$walletId];
                }
            }
        }

        // Lấy transactions với filters và phân trang
        $params = $this->extractQueryPagination($request);
        $filters = [
            'wallet_ids' => $walletIds,
            'type' => $request->input('type'),
            'status' => $request->input('status'),
        ];
        
        $result = $this->walletTransactionService->getTransactionsWithFilters(
            new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            ),
            $filters
        );
        
        $paginator = $result->getData();

        return $this->rendering('transactions/index', [
            'transactions' => fn () => WalletTransactionResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => $request->only(['type', 'status', 'user_id']),
            'canApprove' => $user->role === UserRole::ADMIN->value,
        ]);
    }

    // Xác nhận giao dịch role:adminx
    public function approve(int $id, Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== UserRole::ADMIN->value) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->back();
        }

        $transaction = $this->walletTransactionService->findById($id);
        if (!$transaction) {
            FlashMessage::error(__('common_error.wallet_transaction_not_found'));
            return redirect()->back();
        }

        if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
            FlashMessage::error(__('common_error.wallet_transaction_not_pending'));
            return redirect()->back();
        }

        $txHash = $request->input('tx_hash');

        // Kiểm tra loại giao dịch
        if ((int) $transaction->type === WalletTransactionType::DEPOSIT->value) {
            $result = $this->walletTransactionService->approveDeposit(
                transactionId: $id,
                txHash: $txHash
            );
        } elseif ((int) $transaction->type === WalletTransactionType::WITHDRAW->value) {
            $result = $this->walletTransactionService->approveWithdraw(
                transactionId: $id,
                txHash: $txHash
            );
        } elseif (in_array(
            (int) $transaction->type,
            [
                WalletTransactionType::CAMPAIGN_BUDGET_UPDATE_GOOGLE->value,
                WalletTransactionType::CAMPAIGN_BUDGET_UPDATE_META->value,
            ],
            true
        )) {
            // Với lệnh cập nhật ngân sách chiến dịch: chỉ cập nhật status (tiền đã bị trừ khi tạo lệnh)
            $result = $this->walletTransactionService->updateTransactionStatus(
                transactionId: $id,
                status: WalletTransactionStatus::COMPLETED->value
            );
        } elseif (in_array(
            (int) $transaction->type,
            [
               WalletTransactionType::CAMPAIGN_PAUSE_GOOGLE->value,
               WalletTransactionType::CAMPAIGN_PAUSE_META->value,
               WalletTransactionType::CAMPAIGN_END_GOOGLE->value,
               WalletTransactionType::CAMPAIGN_END_META->value,
            ],
            true
        )) {
            // Với lệnh tạm dừng/kết thúc chiến dịch: chỉ cập nhật status (không trừ tiền, admin xử lý thủ công)
            $result = $this->walletTransactionService->updateTransactionStatus(
                transactionId: $id,
                status: WalletTransactionStatus::COMPLETED->value
            );
        } else {
            FlashMessage::error(__('Loại giao dịch không được hỗ trợ'));
            return redirect()->back();
        }

        if ($result->isSuccess()) {
            FlashMessage::success(__('wallet.flash.transaction_approved'));
        } else {
            FlashMessage::error($result->getMessage());
        }

        return redirect()->back();
    }

    // Hủy giao dịch role:admin
    public function cancel(int $id, Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== UserRole::ADMIN->value) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->back();
        }

        $transaction = $this->walletTransactionService->findById($id);
        if (!$transaction) {
            FlashMessage::error(__('common_error.wallet_transaction_not_found'));
            return redirect()->back();
        }

        if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
            FlashMessage::error(__('common_error.wallet_transaction_not_pending'));
            return redirect()->back();
        }

        // Kiểm tra loại giao dịch
        if ((int) $transaction->type === WalletTransactionType::DEPOSIT->value) {
            // Với deposit, chỉ cập nhật status (chưa cộng tiền nên không cần hoàn lại)
            $result = $this->walletTransactionService->updateTransactionStatus(
                transactionId: $id,
                status: WalletTransactionStatus::CANCELLED->value,
                description: WalletTransactionDescription::DEPOSIT_CANCELLED_ADMIN->value
            );
        } elseif ((int) $transaction->type === \App\Common\Constants\Wallet\WalletTransactionType::WITHDRAW->value) {
            // Với withdraw, hủy và hoàn lại tiền
            $result = $this->walletTransactionService->cancelWithdrawByAdmin($id);
        } elseif (in_array(
            (int) $transaction->type,
            [
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_BUDGET_UPDATE_GOOGLE->value,
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_BUDGET_UPDATE_META->value,
            ],
            true
        )) {
            // Hủy lệnh cập nhật ngân sách chiến dịch và hoàn tiền
            $result = $this->walletTransactionService->cancelCampaignBudgetUpdateByAdmin($id);
        } elseif (in_array(
            (int) $transaction->type,
            [
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_PAUSE_GOOGLE->value,
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_PAUSE_META->value,
            ],
            true
        )) {
            // Hủy lệnh tạm dừng chiến dịch (không cần hoàn tiền vì amount = 0)
            $result = $this->walletTransactionService->cancelCampaignPauseByAdmin($id);
        } elseif (in_array(
            (int) $transaction->type,
            [
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_END_GOOGLE->value,
                \App\Common\Constants\Wallet\WalletTransactionType::CAMPAIGN_END_META->value,
            ],
            true
        )) {
            // Hủy lệnh kết thúc chiến dịch (không cần hoàn tiền vì amount = 0)
            $result = $this->walletTransactionService->cancelCampaignEndByAdmin($id);
        } else {
            FlashMessage::error(__('Loại giao dịch không được hỗ trợ'));
            return redirect()->back();
        }

        if ($result->isSuccess()) {
            FlashMessage::success(__('wallet.flash.transaction_cancelled'));
        } else {
            FlashMessage::error($result->getMessage());
        }

        return redirect()->back();
    }
}

