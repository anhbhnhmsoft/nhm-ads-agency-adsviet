<?php

namespace App\Http\Controllers;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionDescription;
use App\Core\Logging;
use App\Service\CoinRemitterService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class CoinRemitterWebhookController
{
    public function __construct(
        protected CoinRemitterService $coinRemitterService,
        protected WalletTransactionService $walletTransactionService,
    ) {}

    public function handle(Request $request): JsonResponse|Response
    {
        $payload = $request->all();

        if ($request->input('ping') === 'site') {
            Logging::api('CoinRemitterWebhookController@handle: Validator ping', [
                'payload' => $payload,
            ]);

            return $this->ack();
        }

        $invoiceId = $this->extractInvoiceId($payload);
        $transaction = null;
        $invoice = null;

        if ($invoiceId) {
            $transactionResult = $this->walletTransactionService->findByPaymentId($invoiceId);
            if ($transactionResult->isSuccess()) {
                $transaction = $transactionResult->getData();
            }
        }

        if (!$transaction && $this->isWalletWebhookPayload($payload)) {
            $walletWebhookResult = $this->resolveTransactionFromWalletWebhook($payload);
            if ($walletWebhookResult !== null) {
                [$transaction, $invoiceId, $invoice] = $walletWebhookResult;
            }
        }

        if (!$invoiceId) {
            Logging::api('CoinRemitterWebhookController@handle: Missing invoice id', [
                'payload' => $payload,
            ]);

            return $this->ack();
        }

        if (!$transaction) {
            Logging::api('CoinRemitterWebhookController@handle: Transaction not found', [
                'invoice_id' => $invoiceId,
                'payload' => $payload,
            ]);

            return $this->ack();
        }

        $network = (string) ($transaction->network ?? '');

        if ($invoice === null) {
            $invoiceResult = $this->coinRemitterService->getInvoice($network, $invoiceId);
            if ($invoiceResult->isError()) {
                Logging::api('CoinRemitterWebhookController@handle: Failed to verify invoice', [
                    'invoice_id' => $invoiceId,
                    'transaction_id' => $transaction->id,
                    'error' => $invoiceResult->getMessage(),
                ]);

                return response()->json(['error' => 'Failed to verify invoice'], 422);
            }

            $invoice = $invoiceResult->getData();
            $invoice = is_array($invoice) ? $invoice : [];
        }

        $status = $this->coinRemitterService->status($invoice);

        if ($this->coinRemitterService->isPaidStatus($status)) {
            if ((int) $transaction->status !== WalletTransactionStatus::PENDING->value) {
                Logging::api('CoinRemitterWebhookController@handle: Already processed', [
                    'invoice_id' => $invoiceId,
                    'transaction_id' => $transaction->id,
                ]);

                return $this->ack();
            }

            $approveResult = $this->walletTransactionService->approveDeposit(
                transactionId: (int) $transaction->id,
                txHash: $this->coinRemitterService->txHash($invoice, $payload),
            );

            if ($approveResult->isError()) {
                Logging::error('CoinRemitterWebhookController@handle: Failed to approve deposit', [
                    'invoice_id' => $invoiceId,
                    'transaction_id' => $transaction->id,
                    'error' => $approveResult->getMessage(),
                ]);

                return response()->json(['error' => 'Failed to approve deposit'], 500);
            }

            Logging::api('CoinRemitterWebhookController@handle: Deposit approved', [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transaction->id,
            ]);

            return $this->ack();
        }

        if ($this->coinRemitterService->isFailedStatus($status)) {
            if ((int) $transaction->status === WalletTransactionStatus::PENDING->value) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::REJECTED->value,
                );
            }

            Logging::api('CoinRemitterWebhookController@handle: Failed status updated', [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transaction->id,
                'invoice_status' => $status,
            ]);

            return $this->ack();
        }

        if ($status === CoinRemitterService::STATUS_UNDER_PAID) {
            if ((int) $transaction->status === WalletTransactionStatus::PENDING->value) {
                $this->walletTransactionService->updateTransactionStatus(
                    transactionId: (int) $transaction->id,
                    status: WalletTransactionStatus::PENDING->value,
                    description: WalletTransactionDescription::DEPOSIT_UNDERPAID->value,
                );
            }

            Logging::api('CoinRemitterWebhookController@handle: Underpaid status acknowledged', [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transaction->id,
                'invoice_status' => $status,
            ]);

            return $this->ack();
        }

        Logging::api('CoinRemitterWebhookController@handle: Pending status acknowledged', [
            'invoice_id' => $invoiceId,
            'transaction_id' => $transaction->id,
            'invoice_status' => $status,
        ]);

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

    private function extractInvoiceId(array $payload): ?string
    {
        $value = $payload['invoice_id']
            ?? $payload['id']
            ?? $payload['invoice']['id']
            ?? null;

        return $value !== null ? (string) $value : null;
    }

    private function resolveTransactionFromWalletWebhook(array $payload): ?array
    {
        $networks = $this->payloadNetworks($payload);
        $payloadAddress = $this->payloadAddress($payload);
        $candidatesResult = $this->walletTransactionService->findPendingCoinRemitterDeposits($networks);

        if ($candidatesResult->isError()) {
            Logging::api('CoinRemitterWebhookController@handle: Failed to load pending deposits', [
                'payload' => $payload,
                'error' => $candidatesResult->getMessage(),
            ]);

            return null;
        }

        foreach ($candidatesResult->getData() as $transaction) {
            $candidateInvoiceId = (string) ($transaction->payment_id ?? '');
            $candidateNetwork = (string) ($transaction->network ?? '');

            if ($candidateInvoiceId === '' || $candidateNetwork === '') {
                continue;
            }

            $invoiceResult = $this->coinRemitterService->getInvoice($candidateNetwork, $candidateInvoiceId);
            if ($invoiceResult->isError()) {
                Logging::api('CoinRemitterWebhookController@handle: Failed to verify pending candidate', [
                    'invoice_id' => $candidateInvoiceId,
                    'transaction_id' => $transaction->id ?? null,
                    'error' => $invoiceResult->getMessage(),
                ]);

                continue;
            }

            $invoice = $invoiceResult->getData();
            $invoice = is_array($invoice) ? $invoice : [];
            $status = $this->coinRemitterService->status($invoice);
            $invoiceAddress = $this->coinRemitterService->payAddress($invoice);

            if ($payloadAddress && $invoiceAddress && !hash_equals($payloadAddress, $invoiceAddress)) {
                continue;
            }

            if (!$this->coinRemitterService->isPaidStatus($status) && $status !== CoinRemitterService::STATUS_UNDER_PAID) {
                continue;
            }

            Logging::api('CoinRemitterWebhookController@handle: Wallet webhook matched invoice', [
                'invoice_id' => $candidateInvoiceId,
                'transaction_id' => $transaction->id ?? null,
                'invoice_status' => $status,
                'payload_id' => $payload['id'] ?? null,
                'txid' => $payload['txid'] ?? null,
            ]);

            return [$transaction, $candidateInvoiceId, $invoice];
        }

        Logging::api('CoinRemitterWebhookController@handle: No actionable invoice matched wallet webhook', [
            'payload' => $payload,
        ]);

        return null;
    }

    private function isWalletWebhookPayload(array $payload): bool
    {
        return isset($payload['txid'])
            || (isset($payload['address']) && isset($payload['amount']))
            || (isset($payload['coin_symbol']) && isset($payload['amount']));
    }

    private function payloadAddress(array $payload): ?string
    {
        $value = Arr::get($payload, 'address')
            ?? Arr::get($payload, 'payment_address')
            ?? Arr::get($payload, 'invoice.address');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function payloadNetworks(array $payload): ?array
    {
        $symbol = strtoupper(trim((string) (Arr::get($payload, 'coin_symbol') ?? '')));
        if ($symbol === '') {
            return null;
        }

        $networks = [];
        foreach ((array) config('services.coinremitter.networks', []) as $network => $credentials) {
            $coin = strtoupper(trim((string) ($credentials['coin'] ?? '')));
            if ($coin === $symbol) {
                $networks[] = (string) $network;
            }
        }

        return $networks ?: null;
    }
}
