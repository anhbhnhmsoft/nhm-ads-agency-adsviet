<?php

namespace App\Service;

use App\Core\Logging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyExchangeService
{
    private const CACHE_TTL_SECONDS = 21600;

    private const FALLBACK_RATES_TO_USD = [
        'USD' => 1.0,
        'USDT' => 1.0,
        'VND' => 0.000039,
        'INR' => 0.012,
        'EUR' => 1.08,
        'GBP' => 1.27,
        'THB' => 0.027,
        'PHP' => 0.017,
        'IDR' => 0.000062,
        'MYR' => 0.21,
        'SGD' => 0.74,
    ];

    public function targetCurrency(): string
    {
        return strtoupper((string) config('services.exchange_rate.target_currency', 'USD'));
    }

    public function convert(float $amount, ?string $fromCurrency, ?string $toCurrency = null): float
    {
        $from = $this->normalizeCurrency($fromCurrency ?: $this->targetCurrency());
        $to = $this->normalizeCurrency($toCurrency ?: $this->targetCurrency());

        if ($amount == 0.0 || $from === $to) {
            return $amount;
        }

        return $amount * $this->getRate($from, $to);
    }

    public function getRate(string $fromCurrency, ?string $toCurrency = null): float
    {
        $from = $this->normalizeCurrency($fromCurrency);
        $to = $this->normalizeCurrency($toCurrency ?: $this->targetCurrency());

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange-rate:{$from}:{$to}";

        try {
            return (float) Cache::remember(
                $cacheKey,
                self::CACHE_TTL_SECONDS,
                fn () => $this->fetchRate($from, $to)
            );
        } catch (\Throwable $e) {
            Logging::error('CurrencyExchangeService@getRate cache error: ' . $e->getMessage());
            return $this->fallbackRate($from, $to);
        }
    }

    private function fetchRate(string $from, string $to): float
    {
        $baseUrl = rtrim((string) config('services.exchange_rate.base_url', 'https://open.er-api.com/v6/latest'), '/');

        try {
            $response = Http::timeout(3)->get("{$baseUrl}/{$from}");

            if (!$response->ok()) {
                return $this->fallbackRate($from, $to);
            }

            $data = $response->json();
            $rate = $data['rates'][$to] ?? null;

            if (!is_numeric($rate) || (float) $rate <= 0) {
                return $this->fallbackRate($from, $to);
            }

            return (float) $rate;
        } catch (\Throwable $e) {
            Logging::error('CurrencyExchangeService@fetchRate error: ' . $e->getMessage(), [
                'from' => $from,
                'to' => $to,
            ]);

            return $this->fallbackRate($from, $to);
        }
    }

    private function fallbackRate(string $from, string $to): float
    {
        $fromToUsd = self::FALLBACK_RATES_TO_USD[$from] ?? null;
        $toToUsd = self::FALLBACK_RATES_TO_USD[$to] ?? null;

        if ($fromToUsd && $toToUsd) {
            return $fromToUsd / $toToUsd;
        }

        return 1.0;
    }

    private function normalizeCurrency(string $currency): string
    {
        $normalized = strtoupper(trim($currency));

        return $normalized !== '' ? $normalized : $this->targetCurrency();
    }
}
