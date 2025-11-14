<?php

namespace App\Service;

use App\Common\Constants\CommonConstant;
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

    public function handleSendOTP(string $telegramId, int $otp, int $expireTime): ServiceReturn
    {
        try {
            $this->bot->sendMessage([
                'chat_id' => $telegramId,
                'text' => __('auth.forgot_password.otp', ['otp' => $otp, 'expire_time' => $expireTime]),
            ]);
            return ServiceReturn::success();
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

    }
}
