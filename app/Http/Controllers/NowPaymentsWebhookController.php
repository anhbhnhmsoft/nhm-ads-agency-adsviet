<?php

namespace App\Http\Controllers;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Payment\PaymentStatus;
use App\Core\Logging;
use App\Service\NowPaymentsService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NowPaymentsWebhookController
{
    public function __construct(
        protected NowPaymentsService $nowPaymentsService,
        protected WalletTransactionService $walletTransactionService,
    ) {}

    /**
     * Handle webhook callback from NowPayments
     * 
     * Endpoint này nhận IPN (Instant Payment Notification) từ NowPayments khi trạng thái payment thay đổi.
     * NowPayments sẽ tự động POST đến URL này với payload đầy đủ.
     * 
     * Payload nhận được từ NowPayments:
     * {
     *   "payment_id": 123456789,
     *   "parent_payment_id": 987654321,
     *   "invoice_id": null,
     *   "payment_status": "finished|confirmed|waiting|failed|expired",
     *   "pay_address": "address",
     *   "payin_extra_id": null,
     *   "price_amount": 1,
     *   "price_currency": "usd",
     *   "pay_amount": 15,
     *   "actually_paid": 15,
     *   "actually_paid_at_fiat": 0,
     *   "pay_currency": "trx|usdtbsc|...",
     *   "order_id": null,
     *   "order_description": null,
     *   "purchase_id": "123456789",
     *   "outcome_amount": 14.8106,
     *   "outcome_currency": "trx",
     *   "payment_extra_ids": null,
     *   "fee": {
     *     "currency": "btc",
     *     "depositFee": 0.09853637216235617,
     *     "withdrawalFee": 0,
     *     "serviceFee": 0
     *   }
     * }
     * 
     * Response trả về:
     * - Success (confirmed/finished): {"status": "success", "transaction_id": 123, "payload": {...}}
     * - Pending (waiting/confirming): {"status": "pending", "transaction_id": 123, "payload": {...}}
     * - Failed/Expired: {"status": "updated", "transaction_id": 123, "payload": {...}}
     * - Unknown status: {"status": "received", "transaction_id": 123, "payload": {...}}
     * - Error: {"error": "message"}
     * 
     */
    // Xử lý webhook từ NowPayments khi trạng thái thanh toán thay đổi
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('x-nowpayments-sig', '');

            // Xác minh chữ ký webhook
            if (!$this->nowPaymentsService->verifyWebhookSignature($payload, $signature)) {
                Logging::api('NowPaymentsWebhookController@handle: Chữ ký webhook không hợp lệ', [
                    'payload' => $payload,
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $paymentId = $payload['payment_id'] ?? null;
            $paymentStatus = $payload['payment_status'] ?? null;

            if (empty($paymentId)) {
                Logging::api('NowPaymentsWebhookController@handle: Thiếu payment_id trong payload', [
                    'payload' => $payload,
                ]);
                return response()->json(['error' => 'Missing payment_id'], 400);
            }

            return $this->handleWalletDepositPayment($paymentId, $paymentStatus, $payload);
        } catch (\Throwable $e) {
            Logging::error('NowPaymentsWebhookController@handle exception: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Xử lý payment cho wallet deposit (logic cũ)
     */
    private function handleWalletDepositPayment(string $paymentId, ?string $paymentStatus, array $payload): JsonResponse
    {
        // Tìm giao dịch theo payment_id
        $txResult = $this->walletTransactionService->findByPaymentId($paymentId);
        if ($txResult->isError()) {
            Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Không tìm thấy giao dịch với payment_id', [
                'payment_id' => $paymentId,
                'payload' => $payload,
            ]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $transaction = $txResult->getData();

        // Xử lý các trạng thái thanh toán khác nhau
        if ($paymentStatus === PaymentStatus::CONFIRMED->value || $paymentStatus === PaymentStatus::FINISHED->value) {
            // Thanh toán thành công thì duyệt yêu cầu nạp (thao tác DB bên trong transaction)
            $approveResult = DB::transaction(function () use ($transaction, $payload) {
                return $this->walletTransactionService->approveDeposit(
                    transactionId: (int) $transaction->id,
                    txHash: $payload['outcome_hash'] ?? $payload['txid'] ?? null,
                );
            });

            if ($approveResult->isSuccess()) {
                Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Duyệt nạp tiền thành công', [
                    'payment_id' => $payload['payment_id'],
                    'transaction_id' => $transaction->id,
                ]);
                return response()->json([
                    'status' => 'success',
                    'transaction_id' => $transaction->id,
                    'payload' => $payload,
                ]);
            }

            Logging::error('NowPaymentsWebhookController@handleWalletDepositPayment: Failed to approve deposit', [
                'payment_id' => $payload['payment_id'],
                'transaction_id' => $transaction->id,
                'error' => $approveResult->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to approve deposit'], 500);
        } elseif ($paymentStatus === PaymentStatus::FAILED->value || $paymentStatus === PaymentStatus::EXPIRED->value) {
            // Thanh toán thất bại/hết hạn thì cập nhật trạng thái giao dịch thành REJECTED
            $this->walletTransactionService->updateTransactionStatus(
                transactionId: (int) $transaction->id,
                status: WalletTransactionStatus::REJECTED->value,
            );
            Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Thanh toán thất bại/hết hạn', [
                'payment_id' => $payload['payment_id'],
                'transaction_id' => $transaction->id,
                'status' => $paymentStatus,
            ]);
            return response()->json([
                'status' => 'updated',
                'transaction_id' => $transaction->id,
                'payload' => $payload,
            ]);
        } elseif ($paymentStatus === PaymentStatus::WAITING->value || $paymentStatus === PaymentStatus::CONFIRMING->value) {
            // Thanh toán vẫn đang chờ xác nhận
            Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Thanh toán đang chờ xác nhận', [
                'payment_id' => $payload['payment_id'],
                'transaction_id' => $transaction->id,
                'status' => $paymentStatus,
            ]);
            return response()->json([
                'status' => 'pending',
                'transaction_id' => $transaction->id,
                'payload' => $payload,
            ]);
        } elseif ($paymentStatus === PaymentStatus::SENDING->value) {
            // NowPayments đang chuyển tiền về ví của hệ thống
            Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: NowPayments đang chuyển tiền về ví hệ thống', [
                'payment_id' => $payload['payment_id'],
                'transaction_id' => $transaction->id,
                'status' => $paymentStatus,
            ]);
            return response()->json([
                'status' => 'processing',
                'transaction_id' => $transaction->id,
                'payload' => $payload,
            ]);
        } elseif ($paymentStatus === PaymentStatus::PARTIALLY_PAID->value) {
            // Khách thanh toán thiếu, cần kiểm tra thủ công
            Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Khách thanh toán thiếu (partially_paid)', [
                'payment_id' => $payload['payment_id'],
                'transaction_id' => $transaction->id,
                'status' => $paymentStatus,
                'payload' => $payload,
            ]);
            return response()->json([
                'status' => 'partial',
                'transaction_id' => $transaction->id,
                'payload' => $payload,
            ]);
        }

        // Trạng thái không xác định - ghi log và trả về received để tránh bị gọi lại liên tục
        Logging::api('NowPaymentsWebhookController@handleWalletDepositPayment: Trạng thái thanh toán không xác định', [
            'payment_id' => $payload['payment_id'],
            'transaction_id' => $transaction->id,
            'status' => $paymentStatus,
            'payload' => $payload,
        ]);
        return response()->json([
            'status' => 'received',
            'transaction_id' => $transaction->id,
            'payload' => $payload,
        ]);
    }
}
