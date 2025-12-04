<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\User;

class UserAlertService
{
    public function __construct(
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {
    }

    // Gửi thông báo
    public function sendPlainText(User $user, string $telegramMessage, ?callable $emailSender = null): ServiceReturn
    {
        try {
            $hasTelegram = !empty($user->telegram_id);
            $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

            if (!$hasTelegram && !$hasVerifiedEmail) {
                return ServiceReturn::success(data: ['sent' => false, 'channel' => null]);
            }

            // Ưu tiên Telegram
            if ($hasTelegram) {
                $result = $this->telegramService->sendNotification($user->telegram_id, $telegramMessage);
                if ($result->isSuccess()) {
                    return ServiceReturn::success(data: ['sent' => true, 'channel' => 'telegram']);
                }
            }

            // Fallback sang email nếu có cấu hình và callback
            if ($hasVerifiedEmail && $emailSender) {
                $result = $emailSender($this->mailService, $user);
                if ($result instanceof ServiceReturn && $result->isSuccess()) {
                    return ServiceReturn::success(data: ['sent' => true, 'channel' => 'email']);
                }
            }

            return ServiceReturn::error(message: 'send_plain_text_failed');
        } catch (\Throwable $e) {
            Logging::error(
                message: 'UserAlertService@sendPlainText error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}


