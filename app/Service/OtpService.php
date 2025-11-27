<?php

namespace App\Service;

use App\Common\Constants\Otp\Otp;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\ServiceReturn;
use Carbon\Carbon;

class OtpService
{
    public function generateOtp(string $userId, Otp $type, int $expireMinutes = 5): ServiceReturn
    {
        try {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes($expireMinutes);

            // Xác định cache key dựa trên type
            $cacheKeyEnum = $this->getCacheKeyEnum($type);

            // Xóa OTP cũ nếu có
            Caching::clearCache($cacheKeyEnum, $userId);

            // Lưu OTP vào cache
            Caching::setCache(
                key: $cacheKeyEnum,
                value: [
                    'code' => $code,
                    'user_id' => $userId,
                    'type' => $type->value,
                    'expires_at' => $expiresAt->toDateTimeString(),
                ],
                uniqueKey: $userId,
                expire: $expireMinutes
            );

            return ServiceReturn::success(data: [
                'code' => $code,
                'expires_at' => $expiresAt->toDateTimeString(),
                'expire_minutes' => $expireMinutes,
            ]);
        } catch (\Throwable $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xác minh mã OTP từ cache
     * @param string $userId
     * @param string $code
     * @param Otp $type
     * @return ServiceReturn
     */
    public function verifyOtp(string $userId, string $code, Otp $type): ServiceReturn
    {
        try {
            $cacheKeyEnum = $this->getCacheKeyEnum($type);
            $otpData = Caching::getCache($cacheKeyEnum, $userId);

            if (!$otpData) {
                return ServiceReturn::error(message: __('auth.register.validation.otp_invalid'));
            }

            // Kiểm tra mã OTP có đúng không
            if ($otpData['code'] !== $code) {
                return ServiceReturn::error(message: __('auth.register.validation.otp_invalid'));
            }

            // Kiểm tra OTP đã hết hạn chưa
            $expiresAt = Carbon::parse($otpData['expires_at']);
            if (Carbon::now()->gt($expiresAt)) {
                Caching::clearCache($cacheKeyEnum, $userId);
                return ServiceReturn::error(message: __('auth.register.validation.otp_invalid'));
            }

            // Xóa OTP sau khi verify thành công
            Caching::clearCache($cacheKeyEnum, $userId);

            return ServiceReturn::success();
        } catch (\Throwable $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    // Lấy CacheKey enum dựa trên Otp type
    private function getCacheKeyEnum(Otp $type): CacheKey
    {
        return match ($type) {
            Otp::EMAIL_VERIFICATION => CacheKey::CACHE_OTP_EMAIL_VERIFICATION,
            Otp::FORGOT_PASSWORD => CacheKey::CACHE_FORGOT_PASSWORD,
            default => CacheKey::CACHE_EMAIL_REGISTER, // Fallback
        };
    }
}
