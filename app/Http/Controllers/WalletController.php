<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\Logging;
use App\Http\Requests\Wallet\WalletChangePasswordRequest;
use App\Http\Requests\Wallet\WalletMyTopUpRequest;
use App\Http\Requests\Wallet\WalletMyWithdrawRequest;
use App\Http\Requests\Wallet\WalletResetPasswordRequest;
use App\Http\Requests\Wallet\WalletTopUpRequest;
use App\Http\Requests\Wallet\WalletWithdrawRequest;
use App\Service\ConfigService;
use App\Service\NowPaymentsService;
use App\Service\WalletTransactionService;
use App\Service\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected ConfigService $configService,
        protected WalletTransactionService $walletTransactionService,
        protected NowPaymentsService $nowPaymentsService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $walletResult = $this->walletService->getWalletForUser((int) $user->id);
        $wallet = $walletResult->isSuccess() ? $walletResult->getData() : null;
        $walletError = $walletResult->isError() ? $walletResult->getMessage() : null;

        // Lấy cấu hình usdt từ config
        $configResult = $this->configService->getAll();
        $configs = $configResult->isSuccess() ? $configResult->getData() : [];
        $availableNetworks = [];
        if (!empty($configs['BEP20_WALLET_ADDRESS']['value'] ?? null)) {
            $availableNetworks[] = [
                'key' => 'BEP20',
                'config_key' => 'BEP20_WALLET_ADDRESS',
                'address' => $configs['BEP20_WALLET_ADDRESS']['value'],
            ];
        }
        if (!empty($configs['TRC20_WALLET_ADDRESS']['value'] ?? null)) {
            $availableNetworks[] = [
                'key' => 'TRC20',
                'config_key' => 'TRC20_WALLET_ADDRESS',
                'address' => $configs['TRC20_WALLET_ADDRESS']['value'],
            ];
        }

        // Lấy lệnh nạp đang chờ xử lý
        $pending = $wallet 
            ? $this->walletTransactionService->getPendingDepositForWallet((int) $wallet['id'])
            : null;


        return $this->rendering('wallet/index', [
            'wallet' => $wallet,
            'walletError' => $walletError,
            'networks' => $availableNetworks,
            'pending_deposit' => $pending,
        ]);
    }

    public function getMinimalAmount(string $network)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.permission_denied')], 401);
        }

        // Kiểm tra mạng có hợp lệ không (chỉ chấp nhận BEP20 hoặc TRC20)
        if (!in_array(strtoupper($network), ['BEP20', 'TRC20'])) {
            return response()->json(['success' => false, 'message' => __('Mạng không hợp lệ')], 400);
        }

        $result = $this->nowPaymentsService->getMinimalAmount(strtoupper($network), includeFiatEquivalent: true);
        
        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 500);
        }

        $data = $result->getData();
        return response()->json([
            'success' => true,
            'data' => [
                'network' => strtoupper($network),
                'min_amount' => $data['min_amount'] ?? null,
                'fiat_equivalent' => $data['fiat_equivalent'] ?? null, //Giá trị tương được với USD
                'currency_from' => $data['currency_from'] ?? 'usd',
                'currency_to' => $data['currency_to'] ?? null,
            ],
        ]);
    }

    public function create(string $userId, Request $request): RedirectResponse
    {
        $password = $request->string('password')->toString() ?: null;
        $result = $this->walletService->create($userId, $password);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function topUp(string $userId, WalletTopUpRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->walletService->topUp($userId, (float)$data['amount']);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function withdraw(string $userId, WalletWithdrawRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->walletService->withdraw($userId, (float)$data['amount'], $data['password'] ?? null);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function lock(string $userId): RedirectResponse
    {
        $result = $this->walletService->lock($userId);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function unlock(string $userId): RedirectResponse
    {
        $result = $this->walletService->unlock($userId);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function resetPassword(string $userId, WalletResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->walletService->resetPassword($userId, $data['password']);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function changePassword(WalletChangePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->route('login');
        }

        $data = $request->validated();

        $result = $this->walletService->changePassword((int) $user->id, $data['current_password'] ?? null, $data['new_password']);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    
    // Xử lý yêu cầu nạp tiền của user (customer/reseller)
    public function myTopUp(WalletMyTopUpRequest $request): RedirectResponse
    {
        // Kiểm tra user đã đăng nhập chưa
        $user = Auth::user();
        if (!$user) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->route('login');
        }

        Logging::web('WalletController@myTopUp: Start', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
        ]);

        try {
            // Validate dữ liệu từ form (amount, network)
            $data = $request->validated();
            
            // Lấy thông tin khách hàng từ user đăng nhập
            $customerName = $user->name ?: ('User ' . $user->id);

            Logging::web('WalletController@myTopUp: Validation passed', [
                'amount' => $data['amount'],
                'network' => $data['network'],
            ]);

            // Kiểm tra mạng đã chọn có được cấu hình chưa (có địa chỉ ví chưa)
            $configResult = $this->configService->getAll();
            $configs = $configResult->isSuccess() ? $configResult->getData() : [];
            $networkConfigKey = $data['network'] === 'BEP20' ? 'BEP20_WALLET_ADDRESS' : 'TRC20_WALLET_ADDRESS';
            $networkAddress = $configs[$networkConfigKey]['value'] ?? null;
            
            if (empty($networkAddress)) {
                Logging::web('WalletController@myTopUp: Network not configured', [
                    'network' => $data['network'],
                ]);
                FlashMessage::error(__('wallet.network_not_configured'));
                return redirect()->back();
            }

            Logging::web('WalletController@myTopUp: Network address found', [
                'network' => $data['network'],
                'address' => substr($networkAddress, 0, 10) . '...',
            ]);

            // Tạo payment trên NowPayments API
            // NowPayments sẽ trả về payment_id và pay_address (địa chỉ ví để user chuyển tiền)
            $orderId = 'DEPOSIT_' . time() . '_' . $user->id;
            $successUrl = route('wallet_index') . '?payment=success';  // URL redirect sau khi thanh toán thành công (nếu dùng payment page)
            $cancelUrl = route('wallet_index') . '?payment=cancelled';   // URL redirect nếu hủy thanh toán (nếu dùng payment page)
            
            Logging::web('WalletController@myTopUp: Creating payment on NowPayments', [
                'order_id' => $orderId,
                'amount' => $data['amount'],
                'network' => $data['network'],
            ]);
            
            $paymentResult = $this->nowPaymentsService->createPayment(
                amount: (float) $data['amount'],
                network: $data['network'],
                orderId: $orderId,
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
            );

            if ($paymentResult->isError()) {
                Logging::web('WalletController@myTopUp: Payment creation failed', [
                    'error' => $paymentResult->getMessage(),
                    'data' => $paymentResult->getData(),
                ]);
                FlashMessage::error($paymentResult->getMessage());
                return redirect()->back();
            }

            // Lấy payment_id và pay_address từ response của NowPayments
            $paymentData = $paymentResult->getData();
            $paymentId = $paymentData['payment_id'] ?? null;

            // Kiểm tra payment_id có tồn tại không
            if (empty($paymentId)) {
                Logging::web('WalletController@myTopUp: Missing payment_id', [
                    'payment_data' => $paymentData,
                ]);
                FlashMessage::error(__('common_error.wallet_nowpayments_missing_payment_id'));
                return redirect()->back();
            }

            // Tạo deposit order với payment info từ NowPayments
            $expiresAt = now()->addMinutes(15);
            $createResult = $this->walletTransactionService->createDepositOrder(
                userId: (int) $user->id,
                amount: (float) $data['amount'],
                network: $data['network'],
                depositAddress: $networkAddress,
                customerName: $customerName,
                paymentId: $paymentId,
                payAddress: $paymentData['pay_address'] ?? null,
                expiresAt: $expiresAt,
            );

            if ($createResult->isSuccess()) {
                FlashMessage::success(__('wallet.flash.deposit_created'));
            } else {
                Logging::web('WalletController@myTopUp: Failed to create deposit order', [
                    'error' => $createResult->getMessage(),
                ]);
                FlashMessage::error($createResult->getMessage());
            }
            
            return redirect()->back();
        } catch (\Throwable $e) {
            Logging::error('WalletController@myTopUp: Exception occurred', [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            FlashMessage::error(__('common_error.server_error'));
            return redirect()->back();
        }
    }

    public function cancelDeposit(string $transactionId): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->route('login');
        }

        $result = $this->walletTransactionService->cancelDepositByUser($transactionId, (int)$user->id);
        
        if ($result->isSuccess()) {
            FlashMessage::success(__('wallet.flash.deposit_cancelled'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        
        return redirect()->back();
    }

    public function myWithdraw(WalletMyWithdrawRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            FlashMessage::error(__('common_error.permission_denied'));
            return redirect()->route('login');
        }

        try {
            $data = $request->validated();
            
            // Tạo withdraw_info từ form
            $withdrawInfo = [
                'bank_name' => $data['bank_name'],
                'account_holder' => $data['account_holder'],
                'account_number' => $data['account_number'],
            ];

            // Tạo lệnh rút tiền (PENDING, chưa trừ tiền)
            $result = $this->walletTransactionService->createWithdrawOrder(
                userId: (int) $user->id,
                amount: (float) $data['amount'],
                withdrawInfo: $withdrawInfo,
                walletPassword: $data['password'] ?? null
            );

            if ($result->isSuccess()) {
                FlashMessage::success(__('wallet.flash.withdraw_created'));
            } else {
                FlashMessage::error($result->getMessage());
            }
            
            return redirect()->back();
        } catch (\Throwable $e) {
            Logging::error('WalletController@myWithdraw: Exception occurred', [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            FlashMessage::error(__('common_error.server_error'));
            return redirect()->back();
        }
    }
}


