<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use Illuminate\Support\Facades\Http;

class BinanceService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.binance.base_url'), '/');
    }

    public function getUsdtSpotBalance(): ServiceReturn
    {
        $apiKey = config('services.binance.key');
        $secret = config('services.binance.secret');
        if (empty($apiKey) || empty($secret)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình Binance API Key/Secret'));
        }

        try {
            $timestamp = sprintf('%.0f', microtime(true) * 1000);
            $query = http_build_query([
                'timestamp' => $timestamp,
                'recvWindow' => 5000,
            ]);
            $signature = hash_hmac('sha256', $query, $secret);

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $apiKey,
            ])->get($this->baseUrl.'/api/v3/account', [
                'timestamp' => $timestamp,
                'recvWindow' => 5000,
                'signature' => $signature,
            ]);

            if (!$response->ok()) {
                Logging::error(
                    message: 'BinanceService@getUsdtSpotBalance non-200 response: '.$response->status().' '.$response->body()
                );
                return ServiceReturn::error(message: __('Không thể lấy số dư Binance'));
            }

            $data = $response->json();
            $balances = $data['balances'] ?? [];
            foreach ($balances as $asset) {
                if (($asset['asset'] ?? '') === 'USDT') {
                    $free = ($asset['free'] ?? 0);
                    $locked = ($asset['locked'] ?? 0);
                    return ServiceReturn::success(data: [
                        'asset' => 'USDT',
                        'free' => $free,
                        'locked' => $locked,
                        'total' => $free + $locked,
                    ]);
                }
            }

            return ServiceReturn::success(data: [
                'asset' => 'USDT', 'free' => 0.0, 'locked' => 0.0, 'total' => 0.0,
            ]);
        } catch (\Throwable $e) {
            Logging::error('BinanceService@getUsdtSpotBalance error: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Docs binamce: /sapi/v1/asset/transfer

    public function createUniversalTransfer(string $type, string $asset, string $amount, ?string $clientTranId = null): ServiceReturn
    {
        $apiKey = config('services.binance.key');
        $secret = config('services.binance.secret');
        if (empty($apiKey) || empty($secret)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình Binance API Key/Secret'));
        }
        try {
            $timestamp = sprintf('%.0f', microtime(true) * 1000);
            $params = [
                'type' => $type,
                'asset' => $asset,
                'amount' => $amount,
                'timestamp' => $timestamp,
                'recvWindow' => 5000,
            ];
            if (!empty($clientTranId)) {
                $params['clientTranId'] = $clientTranId;
            }
            $signature = hash_hmac('sha256', http_build_query($params), $secret);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $apiKey,
            ])->asForm()->post($this->baseUrl.'/sapi/v1/asset/transfer', $params);

            if (!$response->ok()) {
                $errorBody = $response->body();
                $contentType = $response->header('Content-Type', '');
                
                // Check if response is HTML (happens when endpoint doesn't exist on testnet)
                if (str_contains($contentType, 'text/html') || str_starts_with(trim($errorBody), '<!DOCTYPE') || str_starts_with(trim($errorBody), '<html')) {
                    Logging::error('BinanceService@createUniversalTransfer: API endpoint returned HTML instead of JSON', [
                        'type' => $type,
                        'asset' => $asset,
                        'amount' => $amount,
                        'clientTranId' => $clientTranId,
                        'status' => $response->status(),
                        'endpoint' => '/sapi/v1/asset/transfer',
                        'base_url' => $this->baseUrl,
                    ]);
                    
                    $isTestnet = str_contains($this->baseUrl, 'testnet') || str_contains($this->baseUrl, 'test');
                    $message = $isTestnet
                        ? __('Universal Transfer API không được hỗ trợ trên Binance Test Network. Vui lòng sử dụng API thật hoặc kiểm tra lại endpoint.')
                        : __('Binance API trả về lỗi: Endpoint không tồn tại hoặc không được hỗ trợ (Status: :status)', ['status' => $response->status()]);
                    
                    return ServiceReturn::error(message: $message, data: [
                        'status' => $response->status(),
                        'content_type' => $contentType,
                        'is_html_response' => true,
                    ]);
                }
                
                // Try to parse JSON error response
                $errorData = $response->json();
                
                // Extract error message from Binance response
                $errorMessage = $errorData['msg'] ?? $errorData['message'] ?? 'Không thể tạo lệnh chuyển nội bộ Binance';
                $errorCode = $errorData['code'] ?? null;
                
                Logging::error('BinanceService@createUniversalTransfer error: '.$response->status().' '.substr($errorBody, 0, 500), [
                    'type' => $type,
                    'asset' => $asset,
                    'amount' => $amount,
                    'clientTranId' => $clientTranId,
                    'error_code' => $errorCode,
                ]);
                
                // Provide helpful messages for common error codes
                $helpMessage = '';
                if ($errorCode === -1002) {
                    $helpMessage = ' Vui lòng kiểm tra API Key có quyền "Permits Universal Transfer" trong Binance API Management. Lưu ý: Bạn PHẢI bật "Restrict access to trusted IPs only" trước, sau đó mới bật được "Permits Universal Transfer".';
                } elseif ($errorCode === -2010) {
                    $helpMessage = ' Số dư không đủ để thực hiện transfer.';
                } elseif ($errorCode === -2011) {
                    $helpMessage = ' Loại transfer không hợp lệ hoặc không được hỗ trợ.';
                }
                
                $message = $errorCode 
                    ? __('Không thể tạo lệnh chuyển nội bộ Binance: :message (Code: :code):help', [
                        'message' => $errorMessage,
                        'code' => $errorCode,
                        'help' => $helpMessage,
                    ])
                    : __('Không thể tạo lệnh chuyển nội bộ Binance: :message:help', [
                        'message' => $errorMessage,
                        'help' => $helpMessage,
                    ]);
                
                return ServiceReturn::error(message: $message, data: $errorData);
            }
            $data = $response->json();
            return ServiceReturn::success(data: $data);
        } catch (\Throwable $e) {
            Logging::error('BinanceService@createUniversalTransfer exception: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Query Universal Transfer history, optionally by clientTranId.
    // Docs: /sapi/v1/asset/transfer
    public function getUniversalTransferHistory(?string $clientTranId = null, int $current = 1, int $size = 10): ServiceReturn
    {
        $apiKey = config('services.binance.key');
        $secret = config('services.binance.secret');
        if (empty($apiKey) || empty($secret)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình Binance API Key/Secret'));
        }
        try {
            $timestamp = sprintf('%.0f', microtime(true) * 1000);
            $params = [
                'timestamp' => $timestamp,
                'recvWindow' => 5000,
                'current' => $current,
                'size' => $size,
            ];
            if (!empty($clientTranId)) {
                $params['clientTranId'] = $clientTranId;
            }
            $signature = hash_hmac('sha256', http_build_query($params), $secret);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $apiKey,
            ])->get($this->baseUrl.'/sapi/v1/asset/transfer', $params);

            if (!$response->ok()) {
                Logging::error('BinanceService@getUniversalTransferHistory error: '.$response->status().' '.$response->body());
                return ServiceReturn::error(message: __('Không thể lấy lịch sử chuyển nội bộ Binance'));
            }
            return ServiceReturn::success(data: $response->json());
        } catch (\Throwable $e) {
            Logging::error('BinanceService@getUniversalTransferHistory exception: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Get deposit history to the admin Binance account.
    // Docs: GET /sapi/v1/capital/deposit/hisrec
    public function getDepositHistory(string $asset = 'USDT', ?int $startTime = null, ?int $endTime = null): ServiceReturn
    {
        $apiKey = config('services.binance.key');
        $secret = config('services.binance.secret');
        if (empty($apiKey) || empty($secret)) {
            return ServiceReturn::error(message: __('Thiếu cấu hình Binance API Key/Secret'));
        }
        try {
            $timestamp = sprintf('%.0f', microtime(true) * 1000);
            $params = [
                'timestamp' => $timestamp,
                'recvWindow' => 5000,
                'coin' => $asset,
            ];
            if ($startTime !== null) {
                $params['startTime'] = $startTime;
            }
            if ($endTime !== null) {
                $params['endTime'] = $endTime;
            }
            $signature = hash_hmac('sha256', http_build_query($params), $secret);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $apiKey,
            ])->get($this->baseUrl.'/sapi/v1/capital/deposit/hisrec', $params);

            if (!$response->ok()) {
                Logging::error('BinanceService@getDepositHistory error: '.$response->status().' '.$response->body());
                return ServiceReturn::error(message: __('Không thể lấy lịch sử nạp Binance'));
            }
            return ServiceReturn::success(data: $response->json());
        } catch (\Throwable $e) {
            Logging::error('BinanceService@getDepositHistory exception: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}



