<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\API\Wallet\WalletChangePasswordRequest;
use App\Http\Requests\API\Wallet\WalletDepositRequest;
use App\Http\Requests\API\Wallet\WalletTransactionsRequest;
use App\Http\Requests\API\Wallet\WalletWithdrawRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Service\ConfigService;
use App\Service\NowPaymentsService;
use App\Service\WalletService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\WalletItemResource;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletTransactionService $walletTransactionService,
        protected NowPaymentsService $nowPaymentsService,
        protected ConfigService $configService,
    ) {
    }

    public function me(): JsonResponse
    {
        $result = $this->walletService->myWallet();
        if ($result->isError()){
            return RestResponse::error(message: $result->getMessage());
        }
        $data = $result->getData();
        return RestResponse::success(data: new WalletItemResource($data));
    }

    public function changePassword(WalletChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();
        $result = $this->walletService->changePassword(
            userId: (int) $user->id,
            currentPassword: $data['current_password'] ?? null,
            newPassword: $data['new_password'],
        );
        return $this->handleServiceReturn($result, __('wallet.flash.wallet_password_changed'));
    }

    public function deposit(WalletDepositRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();

        // Lấy địa chỉ ví mạng nạp từ config
        $configResult = $this->configService->getAll();
        $configs = $configResult->isSuccess() ? $configResult->getData() : [];
        $networkConfigKey = $data['network'] === 'BEP20' ? 'BEP20_WALLET_ADDRESS' : 'TRC20_WALLET_ADDRESS';
        $networkAddress = $configs[$networkConfigKey]['value'] ?? null;

        if (empty($networkAddress)) {
            return RestResponse::error(message: __('wallet.network_not_configured'), status: 400);
        }

        // Tạo payment trên NowPayments
        $orderId = 'DEPOSIT_' . time() . '_' . $user->id;
        $successUrl = '';
        $cancelUrl = '';

        $paymentResult = $this->nowPaymentsService->createPayment(
            amount: (float) $data['amount'],
            network: $data['network'],
            orderId: $orderId,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
        );

        if ($paymentResult->isError()) {
            return RestResponse::error(message: $paymentResult->getMessage(), status: 400);
        }

        $paymentData = $paymentResult->getData();
        $paymentId = $paymentData['payment_id'] ?? null;

        if (empty($paymentId)) {
            return RestResponse::error(message: __('common_error.wallet_nowpayments_missing_payment_id'), status: 500);
        }

        $expiresAt = now()->addMinutes(15);
        $createResult = $this->walletTransactionService->createDepositOrder(
            userId: (int) $user->id,
            amount: (float) $data['amount'],
            network: $data['network'],
            depositAddress: $networkAddress,
            customerName: $user->name ?: ('User ' . $user->id),
            paymentId: (string) $paymentId,
            payAddress: $paymentData['pay_address'] ?? null,
            expiresAt: $expiresAt,
        );

        if ($createResult->isError()) {
            return RestResponse::error(message: $createResult->getMessage(), status: 400);
        }

        $transaction = $createResult->getData();
        $transactionArray = $transaction->toArray();
        $transactionArray['id'] = (string) $transaction->id;
        $transactionArray['wallet_id'] = (string) $transaction->wallet_id;

        return RestResponse::success(
            data: [
                'transaction' => $transactionArray,
            ],
            message: __('wallet.flash.deposit_created')
        );
    }

    public function withdraw(WalletWithdrawRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();

        $withdrawInfo = [
            'bank_name' => $data['bank_name'],
            'account_holder' => $data['account_holder'],
            'account_number' => $data['account_number'],
        ];

        $result = $this->walletTransactionService->createWithdrawOrder(
            userId: (int) $user->id,
            amount: (float) $data['amount'],
            withdrawInfo: $withdrawInfo,
            walletPassword: $data['wallet_password'] ?? null
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $result->getData(),
            message: __('wallet.flash.withdraw_created')
        );
    }

    public function TopUp(){

    }


    private function handleServiceReturn($serviceReturn, ?string $successMessage = null): JsonResponse
    {
        if (!$serviceReturn->isSuccess()) {
            return RestResponse::error(message: $serviceReturn->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $serviceReturn->getData(),
            message: $successMessage ?? __('common_success.get_success')
        );
    }

    public function transactions(WalletTransactionsRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();
        $targetUserId = isset($data['user_id']) ? (int) $data['user_id'] : (int) $user->id;

        if ($targetUserId !== (int) $user->id && !$this->walletService->canViewWallet($user, $targetUserId)) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 403);
        }

        $queryListDTO = new QueryListDTO(
            perPage: $data['per_page'] ?? 20,
            page: $data['page'] ?? 1,
            filter: [
                'id' => $data['id'] ?? null,
                'type' => $data['type'] ?? null,
                'status' => $data['status'] ?? null,
                'network' => $data['network'] ?? null,
            ],
            sortBy: 'created_at',
            sortDirection: 'desc',
        );

        $result = $this->walletService->getTransactionsForUser($targetUserId, $queryListDTO);
        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: WalletTransactionResource::collection($result->getData())->response()->getData()
        );
    }
}

