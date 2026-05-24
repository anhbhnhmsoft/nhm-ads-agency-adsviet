<?php

namespace App\Service;

use App\Common\Constants\Config\ConfigName;
use App\Core\Logging;
use App\Core\ServiceReturn;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class CoinRemitterService
{
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_UNDER_PAID = 2;
    public const STATUS_OVER_PAID = 3;
    public const STATUS_EXPIRED = 4;
    public const STATUS_CANCELLED = 5;

    private const SUPPORTED_NETWORKS = ['TRC20', 'BEP20'];

    public function __construct(
        protected ConfigService $configService,
    ) {
    }

    public function isConfigured(string $network): bool
    {
        $credentials = $this->credentials($network);

        return !empty($credentials['coin'])
            && !empty($credentials['api_key'])
            && !empty($credentials['password']);
    }

    public function getCoin(string $network): ?string
    {
        $coin = $this->credentials($network)['coin'] ?? null;

        return is_string($coin) && $coin !== '' ? $coin : null;
    }

    public function getExpireMinutes(): int
    {
        return max((int) config('services.coinremitter.invoice_expire_minutes', 30), 1);
    }

    public function shouldIncludeInvoiceNotifyUrl(): bool
    {
        return (bool) config('services.coinremitter.include_invoice_notify_url', false);
    }

    public function networksForCoinSymbol(string $coinSymbol): ?array
    {
        $symbol = strtoupper(trim($coinSymbol));
        if ($symbol === '') {
            return null;
        }

        $networks = [];
        foreach ($this->supportedNetworks() as $network) {
            $coin = strtoupper(trim((string) ($this->credentials($network)['coin'] ?? '')));
            if ($coin === $symbol) {
                $networks[] = $network;
            }
        }

        return $networks ?: null;
    }

    public function createInvoice(
        string $network,
        float $amount,
        string $orderId,
        ?string $name = null,
        ?string $notifyUrl = null,
        ?string $successUrl = null,
        ?string $failUrl = null,
    ): ServiceReturn {
        if (!$this->isConfigured($network)) {
            return ServiceReturn::error(message: __('wallet.network_not_configured'));
        }

        $payload = [
            'amount' => $this->formatAmount($amount),
            'fiat_currency' => 'USD',
            'expiry_time_in_minutes' => (string) $this->getExpireMinutes(),
            'custom_data1' => $orderId,
        ];

        if ($name) {
            $payload['name'] = substr($name, 0, 30);
        }
        if ($notifyUrl) {
            $payload['notify_url'] = $notifyUrl;
        }
        if ($successUrl) {
            $payload['success_url'] = $successUrl;
        }
        if ($failUrl) {
            $payload['fail_url'] = $failUrl;
        }

        return $this->post($network, 'invoice/create', $payload);
    }

    public function getInvoice(string $network, string $invoiceId): ServiceReturn
    {
        if (!$this->isConfigured($network)) {
            return ServiceReturn::error(message: __('wallet.network_not_configured'));
        }

        return $this->post($network, 'invoice/get', [
            'invoice_id' => $invoiceId,
        ]);
    }

    public function invoiceId(array $invoice): ?string
    {
        $value = Arr::get($invoice, 'invoice_id')
            ?? Arr::get($invoice, 'id')
            ?? Arr::get($invoice, 'invoice.id');

        return $value !== null ? (string) $value : null;
    }

    public function invoiceUrl(array $invoice): ?string
    {
        $value = Arr::get($invoice, 'url')
            ?? Arr::get($invoice, 'invoice_url')
            ?? Arr::get($invoice, 'payment_url')
            ?? Arr::get($invoice, 'checkout_url')
            ?? Arr::get($invoice, 'invoice.url');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function payAddress(array $invoice): ?string
    {
        $value = Arr::get($invoice, 'address')
            ?? Arr::get($invoice, 'wallet_address')
            ?? Arr::get($invoice, 'payment_address')
            ?? Arr::get($invoice, 'invoice.address');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function status(array $invoice): ?int
    {
        $value = Arr::get($invoice, 'status_code')
            ?? Arr::get($invoice, 'invoice.status_code')
            ?? Arr::get($invoice, 'status')
            ?? Arr::get($invoice, 'invoice.status');

        if (is_numeric($value)) {
            return (int) $value;
        }

        return match (strtolower(trim((string) $value))) {
            'pending' => self::STATUS_PENDING,
            'paid' => self::STATUS_PAID,
            'under paid', 'under_paid', 'underpaid' => self::STATUS_UNDER_PAID,
            'over paid', 'over_paid', 'overpaid' => self::STATUS_OVER_PAID,
            'expired' => self::STATUS_EXPIRED,
            'cancelled', 'canceled' => self::STATUS_CANCELLED,
            default => null,
        };
    }

    public function txHash(array $invoice, array $webhookPayload = []): ?string
    {
        $value = Arr::get($webhookPayload, 'txid')
            ?? Arr::get($webhookPayload, 'transaction_id')
            ?? Arr::get($webhookPayload, 'hash')
            ?? Arr::get($invoice, 'txid')
            ?? Arr::get($invoice, 'transaction_id')
            ?? Arr::get($invoice, 'transaction.hash');

        return $value !== null ? (string) $value : null;
    }

    public function isPaidStatus(?int $status): bool
    {
        return in_array($status, [self::STATUS_PAID, self::STATUS_OVER_PAID], true);
    }

    public function isFailedStatus(?int $status): bool
    {
        return in_array($status, [self::STATUS_EXPIRED, self::STATUS_CANCELLED], true);
    }

    private function post(string $network, string $endpoint, array $payload): ServiceReturn
    {
        $credentials = $this->credentials($network);
        $url = rtrim($this->baseUrl(), '/')
            . '/' . ltrim($endpoint, '/');

        try {
            Logging::api('CoinRemitterService@post: Sending request', [
                'endpoint' => $endpoint,
                'network' => $network,
                'url' => $url,
                'payload' => $payload,
                'has_api_key' => !empty($credentials['api_key']),
                'has_password' => !empty($credentials['password']),
            ]);

            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->withHeaders([
                    'X-Api-Key' => (string) $credentials['api_key'],
                    'X-Api-Password' => (string) $credentials['password'],
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Logging::api('CoinRemitterService@post: Non-success response', [
                    'endpoint' => $endpoint,
                    'network' => $network,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ServiceReturn::error(message: $response->body() ?: __('common_error.server_error'));
            }

            $body = $response->json();
            if (!is_array($body)) {
                return ServiceReturn::error(message: __('common_error.server_error'));
            }

            $flag = $body['flag'] ?? $body['success'] ?? null;
            if ($flag !== null && (int) $flag !== 1) {
                Logging::api('CoinRemitterService@post: Error response', [
                    'endpoint' => $endpoint,
                    'network' => $network,
                    'body' => $body,
                ]);

                return ServiceReturn::error(message: (string) ($body['msg'] ?? $body['message'] ?? __('common_error.server_error')));
            }

            $data = $body['data'] ?? $body;
            if (!is_array($data)) {
                return ServiceReturn::error(message: __('common_error.server_error'));
            }

            return ServiceReturn::success(data: $data);
        } catch (\Throwable $exception) {
            Logging::error('CoinRemitterService@post error: '.$exception->getMessage(), [
                'endpoint' => $endpoint,
                'network' => $network,
                'exception' => $exception,
            ]);

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function credentials(string $network): array
    {
        $key = strtoupper($network);
        $fallback = (array) config("services.coinremitter.networks.$key", []);

        if (!in_array($key, self::SUPPORTED_NETWORKS, true)) {
            return $fallback;
        }

        return [
            'coin' => $fallback['coin'] ?? null,
            'api_key' => $this->configValue($this->networkConfigName($key, 'api_key'), $fallback['api_key'] ?? null),
            'password' => $this->configValue($this->networkConfigName($key, 'password'), $fallback['password'] ?? null),
        ];
    }

    private function baseUrl(): string
    {
        return (string) config('services.coinremitter.base_url', 'https://api.coinremitter.com/v1');
    }

    private function configValue(ConfigName $key, mixed $default = null): mixed
    {
        $value = $this->configService->getValue($key, null);

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    private function networkConfigName(string $network, string $field): ConfigName
    {
        return match ($network) {
            'BEP20' => match ($field) {
                'api_key' => ConfigName::COINREMITTER_BEP20_API_KEY,
                'password' => ConfigName::COINREMITTER_BEP20_PASSWORD,
            },
            default => match ($field) {
                'api_key' => ConfigName::COINREMITTER_TRC20_API_KEY,
                'password' => ConfigName::COINREMITTER_TRC20_PASSWORD,
            },
        };
    }

    private function supportedNetworks(): array
    {
        return array_values(array_unique([
            ...self::SUPPORTED_NETWORKS,
            ...array_keys((array) config('services.coinremitter.networks', [])),
        ]));
    }

    private function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.');
    }
}
