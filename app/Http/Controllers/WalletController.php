<?php

namespace App\Http\Controllers;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\Wallet\WalletTransactionDescription;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\Logging;
use App\Http\Requests\Wallet\WalletChangePasswordRequest;
use App\Http\Requests\Wallet\WalletMyTopUpRequest;
use App\Http\Requests\Wallet\WalletMyWithdrawRequest;
use App\Http\Requests\Wallet\WalletResetPasswordRequest;
use App\Http\Requests\Wallet\WalletTopUpRequest;
use App\Http\Requests\Wallet\WalletWithdrawRequest;
use App\Http\Requests\API\Wallet\WalletCampaignBudgetUpdateRequest;
use App\Service\ConfigService;
use App\Service\CoinRemitterService;
use App\Service\PaymentoService;
use App\Service\WalletTransactionService;
use App\Service\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected ConfigService $configService,
        protected WalletTransactionService $walletTransactionService,
        protected CoinRemitterService $coinRemitterService,
        protected PaymentoService $paymentoService,
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

        if ($wallet) {
            $this->reconcileCoinRemitterDepositsForWallet((int) $wallet['id']);
            $this->reconcilePaymentoDepositsForWallet((int) $wallet['id']);

            $walletResult = $this->walletService->getWalletForUser((int) $user->id);
            $wallet = $walletResult->isSuccess() ? $walletResult->getData() : $wallet;
            $walletError = $walletResult->isError() ? $walletResult->getMessage() : $walletError;
        }

        // Lấy cấu hình usdt từ config
        $configResult = $this->configService->getAll();
        $configs = $configResult->isSuccess() ? $configResult->getData() : [];
        $depositMethod = $this->cryptoDepositMethod($configs);
        $availableNetworks = [];

        if ($depositMethod === 'manual' && !empty($configs['BEP20_WALLET_ADDRESS']['value'] ?? null)) {
            $availableNetworks[] = [
                'key' => 'BEP20',
                'config_key' => 'BEP20_WALLET_ADDRESS',
                'address' => $configs['BEP20_WALLET_ADDRESS']['value'],
            ];
        }
        if ($depositMethod === 'coinremitter' && $this->coinRemitterService->isConfigured('BEP20')) {
            $availableNetworks[] = [
                'key' => 'BEP20',
                'config_key' => 'COINREMITTER_BEP20',
                'address' => 'CoinRemitter',
            ];
        }
        if ($depositMethod === 'manual' && !empty($configs['TRC20_WALLET_ADDRESS']['value'] ?? null)) {
            $availableNetworks[] = [
                'key' => 'TRC20',
                'config_key' => 'TRC20_WALLET_ADDRESS',
                'address' => $configs['TRC20_WALLET_ADDRESS']['value'],
            ];
        }
        if ($depositMethod === 'coinremitter' && $this->coinRemitterService->isConfigured('TRC20')) {
            $availableNetworks[] = [
                'key' => 'TRC20',
                'config_key' => 'COINREMITTER_TRC20',
                'address' => 'CoinRemitter',
            ];
        }
        if ($depositMethod === 'paymento' && $this->paymentoService->isConfigured()) {
            $availableNetworks[] = [
                'key' => 'PAYMENTO',
                'config_key' => 'PAYMENTO',
                'address' => 'Paymento',
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

    private function reconcileCoinRemitterDepositsForWallet(int $walletId): void
    {
        $pendingResult = $this->walletTransactionService->findPendingCoinRemitterDeposits(
            limit: 5,
            walletId: $walletId,
        );

        if ($pendingResult->isError()) {
            Logging::web('WalletController@index: Failed to load pending CoinRemitter deposits', [
                'wallet_id' => $walletId,
                'error' => $pendingResult->getMessage(),
            ]);

            return;
        }

        foreach ($pendingResult->getData() as $transaction) {
            $invoiceId = (string) ($transaction->payment_id ?? '');
            $network = (string) ($transaction->network ?? '');

            if ($invoiceId === '' || $network === '' || !$this->coinRemitterService->isConfigured($network)) {
                continue;
            }

            $invoiceResult = $this->coinRemitterService->getInvoice($network, $invoiceId);
            if ($invoiceResult->isError()) {
                Logging::web('WalletController@index: Failed to reconcile CoinRemitter invoice', [
                    'wallet_id' => $walletId,
                    'transaction_id' => $transaction->id ?? null,
                    'invoice_id' => $invoiceId,
                    'network' => $network,
                    'error' => $invoiceResult->getMessage(),
                ]);

                continue;
            }

            $invoice = $invoiceResult->getData();
            $invoice = is_array($invoice) ? $invoice : [];
            $status = $this->coinRemitterService->status($invoice);

            if ($this->coinRemitterService->isPaidStatus($status)) {
                $approveResult = $this->walletTransactionService->approveDeposit(
                    transactionId: (int) $transaction->id,
                    txHash: $this->coinRemitterService->txHash($invoice),
                );

                if ($approveResult->isError()) {
                    Logging::web('WalletController@index: Failed to approve reconciled CoinRemitter deposit', [
                        'wallet_id' => $walletId,
                        'transaction_id' => $transaction->id ?? null,
                        'invoice_id' => $invoiceId,
                        'network' => $network,
                        'invoice_status' => $status,
                        'error' => $approveResult->getMessage(),
                    ]);

                    continue;
                }

                Logging::web('WalletController@index: Reconciled CoinRemitter deposit approved', [
                    'wallet_id' => $walletId,
                    'transaction_id' => $transaction->id ?? null,
                    'invoice_id' => $invoiceId,
                    'network' => $network,
                    'invoice_status' => $status,
                ]);

                continue;
            }

            if ($this->coinRemitterService->isFailedStatus($status)) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::REJECTED->value,
                );

                Logging::web('WalletController@index: Reconciled CoinRemitter deposit rejected', [
                    'wallet_id' => $walletId,
                    'transaction_id' => $transaction->id ?? null,
                    'invoice_id' => $invoiceId,
                    'network' => $network,
                    'invoice_status' => $status,
                ]);

                continue;
            }

            if ($status === CoinRemitterService::STATUS_UNDER_PAID) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::PENDING->value,
                    description: WalletTransactionDescription::DEPOSIT_UNDERPAID->value,
                );
            }
        }
    }

    private function reconcilePaymentoDepositsForWallet(int $walletId): void
    {
        $pendingResult = $this->walletTransactionService->findPendingPaymentoDeposits(
            limit: 5,
            walletId: $walletId,
        );

        if ($pendingResult->isError()) {
            Logging::web('WalletController@index: Failed to load pending Paymento deposits', [
                'wallet_id' => $walletId,
                'error' => $pendingResult->getMessage(),
            ]);

            return;
        }

        foreach ($pendingResult->getData() as $transaction) {
            $token = (string) ($transaction->payment_id ?? '');
            if ($token === '' || !$this->paymentoService->isConfigured()) {
                continue;
            }

            $verifyResult = $this->paymentoService->verifyPayment($token);
            if ($verifyResult->isError()) {
                Logging::web('WalletController@index: Failed to reconcile Paymento payment', [
                    'wallet_id' => $walletId,
                    'transaction_id' => $transaction->id ?? null,
                    'token' => $token,
                    'error' => $verifyResult->getMessage(),
                ]);

                continue;
            }

            $verified = $verifyResult->getData();
            $verified = is_array($verified) ? $verified : [];
            $status = $this->paymentoService->status($verified);

            if ($this->paymentoService->isPaidStatus($status)) {
                $approveResult = $this->walletTransactionService->approveDeposit(
                    transactionId: (int) $transaction->id,
                    txHash: $this->paymentoService->paymentId($verified),
                );

                if ($approveResult->isError()) {
                    Logging::web('WalletController@index: Failed to approve reconciled Paymento deposit', [
                        'wallet_id' => $walletId,
                        'transaction_id' => $transaction->id ?? null,
                        'token' => $token,
                        'payment_status' => $status,
                        'error' => $approveResult->getMessage(),
                    ]);
                }

                continue;
            }

            if ($this->paymentoService->isFailedStatus($status)) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::REJECTED->value,
                );
            }
        }
    }

    /**
     * Trả về thông tin ví dạng JSON cho user hiện tại (dùng cho Inertia/React)
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('common_error.permission_denied'),
            ], 401);
        }

        $walletResult = $this->walletService->getWalletForUser((int) $user->id);
        if ($walletResult->isError()) {
            return response()->json([
                'success' => false,
                'message' => $walletResult->getMessage(),
            ], 400);
        }

        $wallet = $walletResult->getData();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet['balance'] ?? 0,
            ],
        ]);
    }

    private function cryptoDepositMethod(array $configs): string
    {
        $value = strtolower((string) ($configs[ConfigName::CRYPTO_DEPOSIT_METHOD->value]['value'] ?? ''));
        if (in_array($value, ['manual', 'coinremitter', 'paymento'], true)) {
            return $value;
        }

        $hasManualWallet = !empty($configs[ConfigName::BEP20_WALLET_ADDRESS->value]['value'] ?? null)
            || !empty($configs[ConfigName::TRC20_WALLET_ADDRESS->value]['value'] ?? null);

        if ($hasManualWallet) {
            return 'manual';
        }

        return $this->coinRemitterService->isConfigured('BEP20') || $this->coinRemitterService->isConfigured('TRC20')
            ? 'coinremitter'
            : ($this->paymentoService->isConfigured() ? 'paymento' : 'manual');
    }

    /**
     * Web: Tạo yêu cầu cập nhật ngân sách chiến dịch (dùng cho Inertia/React)
     */
    public function campaignBudgetUpdate(WalletCampaignBudgetUpdateRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('common_error.permission_denied'),
            ], 401);
        }

        $data = $request->validated();

        $result = $this->walletTransactionService->createCampaignBudgetUpdateOrder(
            userId: (int) $user->id,
            amount: (float) $data['amount'],
            walletPassword: $data['wallet_password'] ?? null,
            platformType: (int) $data['platform_type'],
            campaignName: $data['campaign_name'] ?? null,
            accountName: $data['account_name'] ?? null,
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('wallet.flash.campaign_budget_update_created'),
            'data' => $result->getData(),
        ]);
    }

    public function campaignPause(\App\Http\Requests\API\Wallet\WalletCampaignPauseRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('common_error.permission_denied'),
            ], 401);
        }

        $data = $request->validated();

        $result = $this->walletTransactionService->createCampaignPauseOrder(
            userId: (int) $user->id,
            platformType: (int) $data['platform_type'],
            campaignName: $data['campaign_name'] ?? null,
            accountName: $data['account_name'] ?? null,
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('wallet.flash.campaign_pause_created'),
            'data' => $result->getData(),
        ]);
    }

    public function campaignEnd(\App\Http\Requests\API\Wallet\WalletCampaignEndRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('common_error.permission_denied'),
            ], 401);
        }

        $data = $request->validated();

        $result = $this->walletTransactionService->createCampaignEndOrder(
            userId: (int) $user->id,
            platformType: (int) $data['platform_type'],
            campaignName: $data['campaign_name'] ?? null,
            accountName: $data['account_name'] ?? null,
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('wallet.flash.campaign_end_created'),
            'data' => $result->getData(),
        ]);
    }

    // API lấy min amount từ NowPayments tạm thời không dùng
    // public function getMinimalAmount(string $network)
    // {
    //     ...
    // }

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

            $configResult = $this->configService->getAll();
            $configs = $configResult->isSuccess() ? $configResult->getData() : [];
            $depositMethod = $this->cryptoDepositMethod($configs);

            if ($depositMethod === 'coinremitter') {
                if (!$this->coinRemitterService->isConfigured($data['network'])) {
                    Logging::web('WalletController@myTopUp: CoinRemitter network not configured', [
                        'network' => $data['network'],
                    ]);
                    FlashMessage::error(__('wallet.network_not_configured'));
                    return redirect()->back();
                }

                $orderId = 'wallet_'.$user->id.'_'.str_replace('.', '', (string) microtime(true));
                $notifyUrl = $this->coinRemitterService->shouldIncludeInvoiceNotifyUrl()
                    ? route('coinremitter_webhook')
                    : null;

                $invoiceResult = $this->coinRemitterService->createInvoice(
                    network: $data['network'],
                    amount: (float) $data['amount'],
                    orderId: $orderId,
                    name: 'Adsviet top up',
                    notifyUrl: $notifyUrl,
                    successUrl: route('wallet_index'),
                    failUrl: route('wallet_index'),
                );

                if ($invoiceResult->isError()) {
                    Logging::web('WalletController@myTopUp: CoinRemitter invoice failed', [
                        'network' => $data['network'],
                        'notify_url_in_payload' => $notifyUrl,
                        'error' => $invoiceResult->getMessage(),
                    ]);
                    FlashMessage::error($invoiceResult->getMessage());
                    return redirect()->back();
                }

                $invoice = $invoiceResult->getData();
                $invoiceId = is_array($invoice) ? $this->coinRemitterService->invoiceId($invoice) : null;
                $invoiceUrl = is_array($invoice) ? $this->coinRemitterService->invoiceUrl($invoice) : null;
                $payAddress = is_array($invoice) ? $this->coinRemitterService->payAddress($invoice) : null;

                if (!$invoiceId) {
                    Logging::error('WalletController@myTopUp: CoinRemitter invoice missing id', [
                        'network' => $data['network'],
                        'invoice' => $invoice,
                    ]);
                    FlashMessage::error(__('common_error.server_error'));
                    return redirect()->back();
                }

                Logging::web('WalletController@myTopUp: CoinRemitter invoice created', [
                    'network' => $data['network'],
                    'order_id' => $orderId,
                    'invoice_id' => $invoiceId,
                    'invoice_url' => $invoiceUrl,
                    'notify_url_in_payload' => $notifyUrl,
                ]);

                $createResult = $this->walletTransactionService->createDepositOrder(
                    userId: (int) $user->id,
                    amount: (float) $data['amount'],
                    network: $data['network'],
                    depositAddress: $payAddress ?: ($invoiceUrl ?: 'CoinRemitter'),
                    customerName: $customerName,
                    customerEmail: $user->email ?? $user->username ?? null,
                    paymentId: $invoiceId,
                    payAddress: $payAddress,
                    expiresAt: now()->addMinutes($this->coinRemitterService->getExpireMinutes()),
                    referenceId: $invoiceUrl ?: $orderId,
                );

                if ($createResult->isSuccess()) {
                    $transaction = $createResult->getData();
                    Logging::web('WalletController@myTopUp: CoinRemitter deposit order created', [
                        'transaction_id' => $transaction->id ?? null,
                        'invoice_id' => $invoiceId,
                    ]);

                    FlashMessage::success(__('wallet.flash.deposit_created'));
                } else {
                    Logging::web('WalletController@myTopUp: Failed to create CoinRemitter deposit order', [
                        'error' => $createResult->getMessage(),
                        'invoice_id' => $invoiceId,
                    ]);
                    FlashMessage::error($createResult->getMessage());
                }

                return redirect()->back();
            }

            if ($depositMethod === 'paymento') {
                if (!$this->paymentoService->isConfigured()) {
                    Logging::web('WalletController@myTopUp: Paymento not configured');
                    FlashMessage::error(__('wallet.network_not_configured'));
                    return redirect()->back();
                }

                $orderId = 'wallet_'.$user->id.'_'.str_replace('.', '', (string) microtime(true));
                $paymentResult = $this->paymentoService->createPayment(
                    amount: (float) $data['amount'],
                    orderId: $orderId,
                    returnUrl: route('wallet_index'),
                    email: $user->email ?? $user->username ?? null,
                );

                if ($paymentResult->isError()) {
                    Logging::web('WalletController@myTopUp: Paymento payment request failed', [
                        'order_id' => $orderId,
                        'error' => $paymentResult->getMessage(),
                    ]);
                    FlashMessage::error($paymentResult->getMessage());
                    return redirect()->back();
                }

                $payment = $paymentResult->getData();
                $token = is_array($payment) ? $this->paymentoService->token($payment) : null;
                if (!$token) {
                    Logging::error('WalletController@myTopUp: Paymento payment missing token', [
                        'payment' => $payment,
                    ]);
                    FlashMessage::error(__('common_error.server_error'));
                    return redirect()->back();
                }

                $gatewayUrl = $this->paymentoService->gatewayUrl($token);
                $createResult = $this->walletTransactionService->createDepositOrder(
                    userId: (int) $user->id,
                    amount: (float) $data['amount'],
                    network: 'PAYMENTO',
                    depositAddress: 'Paymento',
                    customerName: $customerName,
                    customerEmail: $user->email ?? $user->username ?? null,
                    paymentId: $token,
                    payAddress: null,
                    expiresAt: now()->addMinutes($this->paymentoService->getExpireMinutes()),
                    referenceId: $gatewayUrl.'|order:'.$orderId,
                );

                if ($createResult->isSuccess()) {
                    $transaction = $createResult->getData();
                    Logging::web('WalletController@myTopUp: Paymento deposit order created', [
                        'transaction_id' => $transaction->id ?? null,
                        'order_id' => $orderId,
                        'token' => $token,
                    ]);

                    FlashMessage::success(__('wallet.flash.deposit_created'));
                } else {
                    Logging::web('WalletController@myTopUp: Failed to create Paymento deposit order', [
                        'error' => $createResult->getMessage(),
                        'order_id' => $orderId,
                        'token' => $token,
                    ]);
                    FlashMessage::error($createResult->getMessage());
                }

                return redirect()->back();
            }

            // Kiểm tra mạng đã chọn có được cấu hình chưa (có địa chỉ ví chưa)
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

            // Trước đây: tạo payment trên NowPayments và đợi webhook.
            // Hiện tại: chỉ tạo lệnh nạp để admin kiểm tra chuyển khoản và duyệt thủ công.
            $expiresAt = now()->addMinutes(60);
            $createResult = $this->walletTransactionService->createDepositOrder(
                userId: (int) $user->id,
                amount: (float) $data['amount'],
                network: $data['network'],
                depositAddress: $networkAddress,
                customerName: $customerName,
                customerEmail: $user->email ?? $user->username ?? null,
                paymentId: null,
                payAddress: null,
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
            $withdrawType = $data['withdraw_type'] ?? 'bank';
            
            // Tạo withdraw_info theo loại rút tiền
            if ($withdrawType === 'usdt') {
                $withdrawInfo = [
                    'crypto_address' => $data['crypto_address'],
                    'network' => $data['network'],
                    'withdraw_type' => 'usdt',
                ];
            } else {
                $withdrawInfo = [
                    'bank_name' => $data['bank_name'],
                    'account_holder' => $data['account_holder'],
                    'account_number' => $data['account_number'],
                    'withdraw_type' => 'bank',
                ];
            }

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
