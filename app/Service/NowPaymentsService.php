<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsService
{
    private string $baseUrl;
    private string $apiKey;
    private string $ipnSecretKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.nowpayments.base_url'), '/');
        $this->apiKey = (string) config('services.nowpayments.api_key', '');
        $this->ipnSecretKey = (string) config('services.nowpayments.ipn_secret_key', '');
    }

    /**
     * Map network to NowPayments currency code
     * Currency codes from NowPayments API:
     * - BEP20 (BSC): usdtbsc
     * - TRC20: usdttrc20
     * 
     * @param string $network Network (BEP20, TRC20)
     * @return string Currency code for NowPayments
     */
    private function mapNetworkToCurrency(string $network): string
    {
        return match(strtoupper($network)) {
            'BEP20' => 'usdtbsc',  // USDT on BSC (BEP20)
            'TRC20' => 'usdttrc20', // USDT on TRON (TRC20)
            default => 'usdt', // fallback
        };
    }

    /**
     * Get minimal payment amount for network (in USD)
     * Uses API to get real-time minimal amount with fiat equivalent
     * Falls back to hardcoded values if API fails
     * 
     * @param string $network Network (BEP20, TRC20)
     * @return float Minimal amount in USD
     */
    public function getMinimalAmountForNetwork(string $network): float
    {
        return 1.0;
    }
    
    /**
     * Create a payment invoice via NowPayments
     * 
     * @param float $amount Amount in USD
     * @param string $network Network (BEP20, TRC20)
     * @param string $orderId Internal order ID
     * @param string $customerEmail Customer email
     * @param string $customerName Customer name
     * @param string $successUrl URL to redirect after successful payment
     * @param string $cancelUrl URL to redirect if payment is cancelled
     * @return ServiceReturn
     */
    public function createPayment(
        float $amount,
        string $network,
        string $orderId = '',
        string $customerEmail = '',
        string $successUrl = '',
        string $cancelUrl = ''
    ): ServiceReturn {
        if (empty($this->apiKey)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình NowPayments API Key'));
        }

        try {
            $payCurrency = $this->mapNetworkToCurrency($network);
            
            // Chuẩn bị payload gửi lên NowPayments
            
            $payload = [
                'price_amount' => $amount,
                'price_currency' => 'usd',
                'pay_currency' => $payCurrency,
                'order_id' => $orderId ?: uniqid('order_', true),
                'order_description' => "Deposit order #{$orderId}",
            ];

            if (!empty($customerEmail)) {
                $payload['customer_email'] = $customerEmail;
            }

            if (!empty($successUrl)) {
                $payload['success_url'] = $successUrl;
            }

            if (!empty($cancelUrl)) {
                $payload['cancel_url'] = $cancelUrl;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payment', $payload);

            $responseBody = $response->body();
            $statusCode = $response->status();

            $data = [];
            try {
                $data = $response->json();
            } catch (\Exception $e) {
                Logging::error('NowPaymentsService@createPayment: Failed to parse response as JSON', [
                    'status_code' => $statusCode,
                    'body' => $responseBody,
                ]);
                return ServiceReturn::error(message: __('Không thể parse response từ NowPayments'));
            }

            // NowPayments có thể trả về payment_id ngay cả khi status code không phải 200
            // Nếu có payment_id thì coi như payment đã được tạo thành công
            $paymentId = $data['payment_id'] ?? null;
            
            if (!empty($paymentId)) {
                // Payment đã được tạo thành công (có payment_id)
                Logging::web('NowPaymentsService@createPayment success', [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'network' => $network,
                    'http_status' => $statusCode,
                ]);
                return ServiceReturn::success(data: $data);
            }

            // Nếu không có payment_id và status code không OK, thì là lỗi thật
            if (!$response->ok()) {
                Logging::error('NowPaymentsService@createPayment error: ' . $statusCode . ' ' . $responseBody, [
                    'payload' => $payload,
                    'response_data' => $data,
                ]);
                
                $errorMessage = $data['message'] ?? $data['error'] ?? 'Không thể tạo payment trên NowPayments';
                
                return ServiceReturn::error(message: __('Không thể tạo payment: :message', ['message' => $errorMessage]), data: $data);
            }

            // Trường hợp có status code OK nhưng không có payment_id (không nên xảy ra)
            Log::warning('NowPaymentsService@createPayment: Response OK but no payment_id', [
                'status_code' => $statusCode,
                'response_data' => $data,
            ]);
            return ServiceReturn::error(message: __('Không thể tạo payment: Response không hợp lệ từ NowPayments'), data: $data);
        } catch (\Throwable $e) {
            Logging::error('NowPaymentsService@createPayment exception: ' . $e->getMessage(), [
                'exception' => $e,
                'amount' => $amount,
                'network' => $network,
                'order_id' => $orderId,
            ]);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy trạng thái payment theo payment_id từ NowPayments
    public function getPaymentStatus(string $paymentId): ServiceReturn
    {
        if (empty($this->apiKey)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình NowPayments API Key'));
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get($this->baseUrl . '/payment/' . $paymentId);

            if (!$response->ok()) {
                Logging::error('NowPaymentsService@getPaymentStatus error: ' . $response->status() . ' ' . $response->body());
                return ServiceReturn::error(message: __('Không thể lấy trạng thái payment từ NowPayments'));
            }

            $data = $response->json();
            return ServiceReturn::success(data: $data);
        } catch (\Throwable $e) {
            Logging::error('NowPaymentsService@getPaymentStatus exception: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Verify chữ ký webhook (x-nowpayments-sig) từ NowPayments
    // Trả về true nếu chữ ký hợp lệ, false nếu sai / thiếu cấu hình
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        if (empty($this->ipnSecretKey)) {
            Log::warning('NowPaymentsService@verifyWebhookSignature: IPN secret key not configured');
            return false;
        }

        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $expectedSignature = hash_hmac('sha512', $payloadString, $this->ipnSecretKey);
        
        return hash_equals($expectedSignature, $signature);
    }

    // Lấy danh sách currency khả dụng từ NowPayments
    public function getAvailableCurrencies(): ServiceReturn
    {
        if (empty($this->apiKey)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình NowPayments API Key'));
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get($this->baseUrl . '/currencies');

            if (!$response->ok()) {
                Logging::error('NowPaymentsService@getAvailableCurrencies error: ' . $response->status() . ' ' . $response->body());
                return ServiceReturn::error(message: __('Không thể lấy danh sách currencies từ NowPayments'));
            }

            $data = $response->json();
            return ServiceReturn::success(data: $data);
        } catch (\Throwable $e) {
            Logging::error('NowPaymentsService@getAvailableCurrencies exception: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy estimate số crypto sẽ nhận được khi thanh toán amount (USD) trên 1 network cụ thể
    public function getEstimate(float $amount, string $network): ServiceReturn
    {
        if (empty($this->apiKey)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình NowPayments API Key'));
        }

        try {
            $payCurrency = $this->mapNetworkToCurrency($network);
            
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get($this->baseUrl . '/estimate', [
                'amount' => $amount,
                'currency_from' => 'usd',
                'currency_to' => $payCurrency,
            ]);

            if (!$response->ok()) {
                $errorBody = $response->body();
                Logging::error('NowPaymentsService@getEstimate error: ' . $response->status() . ' ' . $errorBody);
                
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Không thể lấy estimate từ NowPayments';
                
                return ServiceReturn::error(message: __('Không thể lấy estimate: :message', ['message' => $errorMessage]), data: $errorData);
            }

            $data = $response->json();
            return ServiceReturn::success(data: $data);
        } catch (\Throwable $e) {
            Logging::error('NowPaymentsService@getEstimate exception: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy số tiền tối thiểu cho 1 lần thanh toán theo network (mock data)
    // includeFiatEquivalent = true: trả thêm trường fiat_equivalent (USD) để FE hiển thị
    public function getMinimalAmount(string $network, bool $includeFiatEquivalent = true): ServiceReturn
    {
        $data = [
            'network' => strtoupper($network),
            'min_amount' => 1.0,
            'currency_from' => 'usd',
            'currency_to' => $this->mapNetworkToCurrency($network),
        ];

        if ($includeFiatEquivalent) {
            $data['fiat_equivalent'] = 1.0;
        }

        return ServiceReturn::success(data: $data);
    }
}

