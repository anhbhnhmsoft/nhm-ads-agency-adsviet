<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\API\Wallet\WalletChangePasswordRequest;
use App\Http\Requests\API\Wallet\WalletDepositRequest;
use App\Http\Requests\API\Wallet\WalletWithdrawRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Service\ConfigService;
use App\Service\NowPaymentsService;
use App\Service\WalletService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\WalletItemResource;
use Illuminate\Http\Request;
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

    /**
     * API Nạp tiền vào ví
     * @param WalletDepositRequest $request
     * @return JsonResponse
     */
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

        return RestResponse::success(
            data: [
                'transaction_id' => (string) $transaction->id,
                'pay_address' => (string)$transaction->pay_address,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            message: __('wallet.flash.deposit_created')
        );
    }

    /**
     * API Kiểm tra nạp tiền
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDeposit(Request $request): JsonResponse
    {
        $transactionId = $request->get('transaction_id') ?? null;
        if (empty($transactionId)) {
            return RestResponse::error(message: __('common_error.wallet_missing_transaction_id'), status: 400);
        }
        $result = $this->walletTransactionService->checkDepositStatus($transactionId);
        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }
        $isSuccess = $result->getData();
        return RestResponse::success(
            data: [
                'is_success' => $isSuccess,
            ],
            message: __('wallet.flash.deposit_checked')
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

    public function transactions(Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->walletService->getTransactionsForUser(new QueryListDTO(
            perPage: $params->get('per_page'),
            page: $params->get('page'),
            filter: $params->get('filter'),
            sortBy: $params->get('sort_by'),
            sortDirection: $params->get('direction'),
        ));
        return RestResponse::success(
            data: WalletTransactionResource::collection($result->getData())->response()->getData()
        );
    }
}

