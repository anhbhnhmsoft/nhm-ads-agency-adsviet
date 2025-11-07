<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\NotificationRepository;

class NotificationService
{
    public function __construct(protected NotificationRepository $notificationRepository)
    {
    }

    public function send(int $userId, string $title, string $description, array $data = [], int $type = 0): ServiceReturn
    {
        try {
            $this->notificationRepository->create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'data' => empty($data) ? null : json_encode($data, JSON_UNESCAPED_UNICODE),
                'type' => $type,
                'status' => 0,
            ]);
            return ServiceReturn::success();
        } catch (\Throwable $e) {
            Logging::error(message: 'NotificationService@send: '.$e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}


