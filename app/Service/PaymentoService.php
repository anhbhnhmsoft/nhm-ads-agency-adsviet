<?php

namespace App\Service;

use App\Common\Constants\Config\ConfigName;
use App\Core\Logging;
use App\Core\ServiceReturn;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PaymentoService
{
    public const STATUS_INITIALIZE = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_PARTIAL_PAID = 2;
    public const STATUS_WAITING_TO_CONFIRM = 3;
    public const STATUS_TIMEOUT = 4;
    public const STATUS_USER_CANCELED = 5;
    public const STATUS_PAID = 7;
    public const STATUS_APPROVE = 8;
    public const STATUS_REJECT = 9;

    public function __construct(
        protected ConfigService $configService,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function hasWebhookSecret(): bool
    {
        return $this->secretKey() !== '';
    }

    public function getExpireMinutes(): int
    {
        return max((int) config('services.paymento.expire_minutes', 30), 1);
    }

    public function createPayment(
        float $amount,
        string $orderId,
        ?string $returnUrl = null,
        ?string $email = null,
    ): ServiceReturn {
        if (!$this->isConfigured()) {
            return ServiceReturn::error(message: __('wallet.network_not_configured'));
        }

        $payload = [
            'fiatAmount' => $this->formatAmount($amount),
            'fiatCurrency' => 'USD',
            'orderId' => $orderId,
            'Speed' => (int) config('services.paymento.speed', 1),
            'additionalData' => [
                ['key' => 'provider', 'value' => 'paymento'],
                ['key' => 'order_id', 'value' => $orderId],
            ],
        ];

        if ($returnUrl) {
            $payload['ReturnUrl'] = $returnUrl;
        }

        if ($email) {
            $payload['EmailAddress'] = $email;
        }

        return $this->post('payment/request', $payload);
    }

    public function verifyPayment(string $token): ServiceReturn
    {
        if (!$this->isConfigured()) {
            return ServiceReturn::error(message: __('wallet.network_not_configured'));
        }

        return $this->post('payment/verify', [
            'token' => $token,
        ]);
    }

    public function gatewayUrl(string $token): string
    {
        return rtrim((string) config('services.paymento.gateway_url', 'https://app.paymento.io/gateway'), '?')
            . '?token=' . urlencode($token);
    }

    public function token(array $payload): ?string
    {
        $value = Arr::get($payload, 'Token')
            ?? Arr::get($payload, 'token')
            ?? Arr::get($payload, 'body')
            ?? Arr::get($payload, 'body.token');

        $value = is_string($value) ? trim($value) : $value;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function orderId(array $payload): ?string
    {
        $value = Arr::get($payload, 'OrderId')
            ?? Arr::get($payload, 'orderId')
            ?? Arr::get($payload, 'body.orderId');

        return $value !== null ? (string) $value : null;
    }

    public function paymentId(array $payload): ?string
    {
        $value = Arr::get($payload, 'PaymentId')
            ?? Arr::get($payload, 'paymentId')
            ?? Arr::get($payload, 'body.paymentId');

        return $value !== null ? (string) $value : null;
    }

    public function status(array $payload): ?int
    {
        $value = Arr::get($payload, 'OrderStatus')
            ?? Arr::get($payload, 'orderStatus')
            ?? Arr::get($payload, 'body.orderStatus')
            ?? Arr::get($payload, 'status');

        if (is_numeric($value)) {
            return (int) $value;
        }

        return match (strtolower(trim((string) $value))) {
            'initialize' => self::STATUS_INITIALIZE,
            'pending' => self::STATUS_PENDING,
            'partialpaid', 'partial_paid', 'partial paid' => self::STATUS_PARTIAL_PAID,
            'waitingtoconfirm', 'waiting_to_confirm', 'waiting to confirm' => self::STATUS_WAITING_TO_CONFIRM,
            'timeout', 'expired' => self::STATUS_TIMEOUT,
            'usercanceled', 'user_canceled', 'user canceled', 'cancelled', 'canceled' => self::STATUS_USER_CANCELED,
            'paid' => self::STATUS_PAID,
            'approve', 'approved' => self::STATUS_APPROVE,
            'reject', 'rejected' => self::STATUS_REJECT,
            default => null,
        };
    }

    public function isPaidStatus(?int $status): bool
    {
        return in_array($status, [self::STATUS_PAID, self::STATUS_APPROVE], true);
    }

    public function isFailedStatus(?int $status): bool
    {
        return in_array($status, [self::STATUS_TIMEOUT, self::STATUS_USER_CANCELED, self::STATUS_REJECT], true);
    }

    public function isPartialStatus(?int $status): bool
    {
        return $status === self::STATUS_PARTIAL_PAID;
    }

    public function verifySignature(string $rawPayload, ?string $receivedSignature): bool
    {
        $secret = $this->secretKey();
        if ($secret === '' || $receivedSignature === null || trim($receivedSignature) === '') {
            return false;
        }

        $calculated = strtoupper(hash_hmac('sha256', $rawPayload, $secret));
        $received = strtoupper(trim($receivedSignature));

        return hash_equals($calculated, $received);
    }

    private function post(string $endpoint, array $payload): ServiceReturn
    {
        $url = rtrim($this->baseUrl(), '/') . '/' . ltrim($endpoint, '/');

        try {
            Logging::api('PaymentoService@post: Sending request', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'has_api_key' => $this->apiKey() !== '',
            ]);

            $response = Http::asJson()
                ->accept('text/plain')
                ->timeout(30)
                ->withHeaders([
                    'Api-key' => $this->apiKey(),
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Logging::api('PaymentoService@post: Non-success response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ServiceReturn::error(message: $response->body() ?: __('common_error.server_error'));
            }

            $body = $response->json();
            if (!is_array($body)) {
                return ServiceReturn::error(message: __('common_error.server_error'));
            }

            if (($body['success'] ?? true) === false) {
                return ServiceReturn::error(message: (string) ($body['message'] ?? __('common_error.server_error')));
            }

            return ServiceReturn::success(data: $body);
        } catch (\Throwable $exception) {
            Logging::error('PaymentoService@post error: '.$exception->getMessage(), [
                'endpoint' => $endpoint,
                'exception' => $exception,
            ]);

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function apiKey(): string
    {
        $value = $this->configService->getValue(ConfigName::PAYMENTO_API_KEY, config('services.paymento.api_key'));

        return is_string($value) ? trim($value) : '';
    }

    private function secretKey(): string
    {
        $value = $this->configService->getValue(ConfigName::PAYMENTO_SECRET_KEY, config('services.paymento.secret_key'));

        return is_string($value) ? trim($value) : '';
    }

    private function baseUrl(): string
    {
        return (string) config('services.paymento.base_url', 'https://api.paymento.io/v1');
    }

    private function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.');
    }
}
