<?php

namespace App\Http\Controllers;

use App\Common\Constants\Wallet\WalletTransactionDescription;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Core\Logging;
use App\Service\PaymentoService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentoWebhookController
{
    public function __construct(
        protected PaymentoService $paymentoService,
        protected WalletTransactionService $walletTransactionService,
    ) {
    }

    public function handle(Request $request): JsonResponse|Response
    {
        $payload = $request->all();
        $rawPayload = $request->getContent();
        $signature = $request->header('X-HMAC-SHA256-SIGNATURE')
            ?? $request->header('X-Hmac-Sha256-Signature')
            ?? $request->header('HMAC_SHA256_SIGNATURE');

        if (!$this->paymentoService->verifySignature($rawPayload, $signature)) {
            Logging::api('PaymentoWebhookController@handle: Invalid signature', [
                'payload' => $payload,
                'has_signature' => !empty($signature),
                'has_secret' => $this->paymentoService->hasWebhookSecret(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $token = $this->paymentoService->token($payload);
        if (!$token) {
            Logging::api('PaymentoWebhookController@handle: Missing token', [
                'payload' => $payload,
            ]);

            return $this->ack();
        }

        $transactionResult = $this->walletTransactionService->findByPaymentId($token);
        if ($transactionResult->isError()) {
            Logging::api('PaymentoWebhookController@handle: Transaction not found', [
                'token' => $token,
                'payload' => $payload,
            ]);

            return $this->ack();
        }

        $transaction = $transactionResult->getData();
        $verifyResult = $this->paymentoService->verifyPayment($token);
        if ($verifyResult->isError()) {
            Logging::api('PaymentoWebhookController@handle: Failed to verify payment', [
                'token' => $token,
                'transaction_id' => $transaction->id ?? null,
                'error' => $verifyResult->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to verify payment'], 422);
        }

        $verified = $verifyResult->getData();
        $verified = is_array($verified) ? $verified : [];
        $verifiedOrderId = $this->paymentoService->orderId($verified);
        $payloadOrderId = $this->paymentoService->orderId($payload);
        $transactionOrderId = $this->paymentoOrderId((string) ($transaction->reference_id ?? ''));

        if ($transactionOrderId !== null && $verifiedOrderId !== null && !hash_equals($transactionOrderId, $verifiedOrderId)) {
            Logging::api('PaymentoWebhookController@handle: Verified order mismatch', [
                'token' => $token,
                'transaction_id' => $transaction->id ?? null,
                'transaction_order_id' => $transactionOrderId,
                'verified_order_id' => $verifiedOrderId,
                'payload_order_id' => $payloadOrderId,
            ]);

            return response()->json(['error' => 'Order mismatch'], 422);
        }

        $status = $this->paymentoService->status($payload);

        if ($this->paymentoService->isPaidStatus($status)) {
            if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                return $this->ack();
            }

            $approveResult = $this->walletTransactionService->approveDeposit(
                transactionId: (int) $transaction->id,
                txHash: $this->paymentoService->paymentId($payload),
            );

            if ($approveResult->isError()) {
                Logging::error('PaymentoWebhookController@handle: Failed to approve deposit', [
                    'token' => $token,
                    'transaction_id' => $transaction->id,
                    'error' => $approveResult->getMessage(),
                ]);

                return response()->json(['error' => 'Failed to approve deposit'], 500);
            }

            Logging::api('PaymentoWebhookController@handle: Deposit approved', [
                'token' => $token,
                'transaction_id' => $transaction->id,
                'order_status' => $status,
            ]);

            return $this->ack();
        }

        if ($this->paymentoService->isFailedStatus($status)) {
            if ((int) $transaction->status === WalletTransactionStatus::PENDING->value) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::REJECTED->value,
                );
            }

            return $this->ack();
        }

        if ($this->paymentoService->isPartialStatus($status)) {
            $this->walletTransactionService->updateTransactionStatus(
                transactionId: (int) $transaction->id,
                status: WalletTransactionStatus::PENDING->value,
                description: WalletTransactionDescription::DEPOSIT_UNDERPAID->value,
            );
        }

        return $this->ack();
    }

    private function ack(): Response
    {
        return response('OK', 200, [
            'Cache-Control' => 'no-store',
            'Content-Length' => '2',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function paymentoOrderId(string $referenceId): ?string
    {
        if (preg_match('/(?:^|[|])order:([^|]+)/', $referenceId, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
