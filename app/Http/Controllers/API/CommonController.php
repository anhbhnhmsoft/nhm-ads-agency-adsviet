<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Service\TelegramService;
use Illuminate\Http\Request;

class CommonController extends Controller
{

    public function __construct(
        protected TelegramService $telegramService,
    )
    {
    }

    public function getTelegramConfig(): \Illuminate\Http\JsonResponse
    {
        $botUsername = config('services.telegram.bot_username');
        $telegramBotId = config('services.telegram.bot_id');
        return RestResponse::success([
            'bot_username' => $botUsername,
            'telegram_bot_id' => $telegramBotId,
            'telegram_callback_url' => route('api.auth.telegram.callback'),
        ]);
    }

    public function handleTelegramWebhook(Request $request): void
    {
        $this->telegramService->handleWebhook();
    }
}
