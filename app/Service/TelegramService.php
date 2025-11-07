<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use Telegram\Bot\Api;

class TelegramService
{
    public function __construct(protected Api $bot)
    {
    }

    public function handleSendOTP(string $telegramId): ServiceReturn
    {
        $cacheOtp = Caching::getCache(
            key: CacheKey::CACHE_TELEGRAM_OTP,
            uniqueKey: $telegramId,
        );
        if ($cacheOtp) {
           Caching::clearCache(key: CacheKey::CACHE_TELEGRAM_OTP, uniqueKey: $telegramId);
        }
        // sinh otp
        $otp = rand(100000, 999999);
        // Thời gian hết hạn OTP (tính theo phút)
        $expireMin = 30;
        Caching::setCache(
            key: CacheKey::CACHE_TELEGRAM_OTP,
            value: $otp,
            uniqueKey: $telegramId,
            expire: $expireMin,
        );
        try {
            $this->bot->sendMessage([
                'chat_id' => $telegramId,
                'text' => __('auth.forgot_password.otp', ['otp' => $otp, 'expire_min' => $expireMin]),
            ]);
            return ServiceReturn::success(data:[
                'expire_min' => $expireMin,
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Error TelegramService@handleSendOTP: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('auth.forgot_password.error_send_otp'));
        }

    }

    public function handleWebhook(): void
    {
//        try {
//            $test = $this->bot->getWebhookUpdate();
//            dd($test);
//        }catch (\Exception $exception){
//            dd($exception);
//        }
    }
}
