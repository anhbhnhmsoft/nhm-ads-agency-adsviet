<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PlatformTokenHealthService
{
    public function check(int $platform, array $config): array
    {
        try {
            return match ($platform) {
                PlatformType::META->value => $this->checkMeta($config),
                PlatformType::GOOGLE->value => $this->checkGoogle($config),
                default => $this->unknown('Nền tảng không được hỗ trợ.'),
            };
        } catch (\Throwable $e) {
            return $this->invalid('Không thể kiểm tra token/key: ' . $e->getMessage());
        }
    }

    private function checkMeta(array $config): array
    {
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        $appId = trim((string) ($config['app_id'] ?? ''));
        $appSecret = trim((string) ($config['app_secret'] ?? ''));

        if ($accessToken === '' || $appId === '' || $appSecret === '') {
            return $this->invalid('Thiếu App ID, App Secret hoặc Access Token để kiểm tra Meta.');
        }

        $response = Http::timeout(15)->get('https://graph.facebook.com/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $appId . '|' . $appSecret,
        ]);

        if (!$response->successful()) {
            return $this->invalid(
                $response->json('error.message')
                    ?: $response->json('error.error_user_msg')
                    ?: 'Meta trả về lỗi khi kiểm tra token.'
            );
        }

        $data = (array) $response->json('data', []);
        if (!($data['is_valid'] ?? false)) {
            return $this->invalid('Meta token không còn hợp lệ.', $data);
        }

        $expiresAt = isset($data['expires_at']) && (int) $data['expires_at'] > 0
            ? Carbon::createFromTimestamp((int) $data['expires_at'])
            : null;

        return [
            'status' => 'valid',
            'message' => $expiresAt
                ? 'Meta token còn hiệu lực.'
                : 'Meta token còn hiệu lực và không trả về thời điểm hết hạn.',
            'checked_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_in_seconds' => $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : null,
            'expires_label' => $expiresAt ? $this->humanTimeLeft($expiresAt) : 'Không có hạn cố định',
            'raw' => [
                'type' => $data['type'] ?? null,
                'app_id' => $data['app_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'scopes' => $data['scopes'] ?? [],
            ],
        ];
    }

    private function checkGoogle(array $config): array
    {
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $refreshToken = trim((string) ($config['refresh_token'] ?? ''));
        $developerToken = trim((string) ($config['developer_token'] ?? ''));

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '' || $developerToken === '') {
            return $this->invalid('Thiếu Client ID, Client Secret, Refresh Token hoặc Developer Token để kiểm tra Google.');
        }

        $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            return $this->invalid(
                $response->json('error_description')
                    ?: $response->json('error')
                    ?: 'Google trả về lỗi khi làm mới access token.'
            );
        }

        $expiresIn = (int) $response->json('expires_in', 0);
        $expiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

        return [
            'status' => 'valid',
            'message' => 'Google refresh token còn hiệu lực. Access token mới đã được cấp để kiểm tra.',
            'checked_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_in_seconds' => $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : null,
            'expires_label' => $expiresAt
                ? 'Access token kiểm tra còn ' . $this->humanTimeLeft($expiresAt)
                : 'Refresh token còn hiệu lực, không có hạn cố định',
            'raw' => [
                'token_type' => $response->json('token_type'),
                'scope' => $response->json('scope'),
                'refresh_token_status' => 'valid',
            ],
        ];
    }

    private function invalid(string $message, array $raw = []): array
    {
        return [
            'status' => 'invalid',
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
            'expires_at' => null,
            'expires_in_seconds' => null,
            'expires_label' => 'Không hợp lệ',
            'raw' => $raw,
        ];
    }

    private function unknown(string $message): array
    {
        return [
            'status' => 'unknown',
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
            'expires_at' => null,
            'expires_in_seconds' => null,
            'expires_label' => 'Chưa xác định',
            'raw' => [],
        ];
    }

    private function humanTimeLeft(Carbon $expiresAt): string
    {
        if ($expiresAt->isPast()) {
            return 'đã hết hạn';
        }

        $seconds = (int) now()->diffInSeconds($expiresAt);
        $days = intdiv($seconds, 86400);
        if ($days > 0) {
            return $days . ' ngày';
        }

        $hours = intdiv($seconds, 3600);
        if ($hours > 0) {
            return $hours . ' giờ';
        }

        $minutes = max(1, intdiv($seconds, 60));
        return $minutes . ' phút';
    }
}
