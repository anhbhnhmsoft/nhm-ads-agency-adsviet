<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Http\Requests\API\Wallet\WalletChangePasswordRequest;
use App\Http\Requests\API\Wallet\WalletWithdrawRequest;
use App\Repositories\WalletRepository;
use App\Service\WalletService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletTransactionService $walletTransactionService,
        protected WalletRepository $walletRepository,
    ) {
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $result = $this->walletService->getWalletForUser((int) $user->id);
        return $this->handleServiceReturn($result);
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

    public function withdraw(WalletWithdrawRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $data = $request->validated();

        $wallet = $this->walletRepository->findByUserId((int) $user->id);
        if ($wallet && !empty($wallet->password)) {
            if (empty($data['wallet_password']) || !Hash::check($data['wallet_password'], $wallet->password)) {
                return RestResponse::error(message: __('Mật khẩu ví không chính xác'), status: 400);
            }
        }

        $withdrawInfo = [
            'bank_name' => $data['bank_name'],
            'account_holder' => $data['account_holder'],
            'account_number' => $data['account_number'],
        ];

        $result = $this->walletTransactionService->createWithdrawOrder(
            userId: (int) $user->id,
            amount: (float) $data['amount'],
            withdrawInfo: $withdrawInfo
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
            message: $successMessage ?? __('common_success.process_success')
        );
    }
}

