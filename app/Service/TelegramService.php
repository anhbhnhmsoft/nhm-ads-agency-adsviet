<?php

namespace App\Service;

use App\Common\Constants\NotificationType\NotificationType;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\NotificationRepository;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Auth;

class TelegramService
{
    public function __construct(
        protected Api $bot,
        protected NotificationRepository $notificationRepository,
    ) {
    }

    private function createNotification(
        ?int $userId,
        string $title,
        string $description,
        NotificationType $type,
        ?array $data = null
    ): void {
        if (!$userId) {
            return;
        }
        try {
            $this->notificationRepository->create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'data' => $data ? json_encode($data) : null,
                'type' => $type->value,
                'status' => 0,
            ]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'TelegramService@createNotification: ' . $e->getMessage(),
                exception: $e
            );
        }
    }

    public function handleSendOTP(string $telegramId, int $otp, int $expireTime): ServiceReturn
    {
        $user = Auth::user();
        try {
            $this->bot->sendMessage([
                'chat_id' => $telegramId,
                'text' => __('auth.forgot_password.otp', ['otp' => $otp, 'expire_time' => $expireTime]),
            ]);
            $this->createNotification(
                userId: $user?->id,
                title: 'OTP',
                description: __('auth.forgot_password.otp', ['otp' => $otp, 'expire_time' => $expireTime]),
                type: NotificationType::OTP,
                data: ['otp' => $otp, 'expire_time' => $expireTime]
            );
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

    /**
     * Gửi thông báo đến Telegram group/channel hoặc user
     */
    public function sendNotification(
        string $chatId,
        string $message,
        ?int $userId = null,
        NotificationType $type = NotificationType::WALLET,
        ?array $data = null
    ): ServiceReturn
    {
        try {
            
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
            $this->createNotification(
                userId: $userId,
                title: 'Telegram',
                description: $message,
                type: $type,
                data: $data
            );
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Error TelegramService@sendNotification: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('ticket.telegram_notification_failed'));
        }
    }

    /**
     * Gửi thông báo ticket mới đến nhiều chat IDs
     */
    public function sendTicketNotification(
        array $chatIds,
        string $message,
        ?int $userId = null,
        NotificationType $type = NotificationType::WALLET,
        ?array $data = null
    ): void
    {
        foreach ($chatIds as $chatId) {
            if (!empty($chatId)) {
                $this->sendNotification($chatId, $message, $userId, $type, $data);
            }
        }
    }

    public function getTelegramUserInfo(string $telegramId): ServiceReturn
    {
        try {
            $chat = $this->bot->getChat(['chat_id' => $telegramId]);
            
            $username = $chat->getUsername() ?? null;
            $firstName = $chat->getFirstName() ?? null;
            $lastName = $chat->getLastName() ?? null;
            
            return ServiceReturn::success(data: [
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim(($firstName ?? '') . ' ' . ($lastName ?? '')),
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Error TelegramService@getTelegramUserInfo: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: 'Không thể lấy thông tin từ Telegram');
        }
    }
}
