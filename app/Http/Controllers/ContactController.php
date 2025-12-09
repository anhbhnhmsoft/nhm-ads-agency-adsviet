<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Service\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService,
    ) {
    }

    /**
     * Hiển thị trang liên hệ
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Thông tin liên hệ cố định
        $contactInfo = [
            'telegram' => '@advietagency',
            'whatsapp' => '+84987440088',
            'channel' => 'https://t.me/advietagencychannel',
        ];

        // Lấy thông tin Telegram user nếu có telegram_id
        $telegramUserInfo = null;
        if (!empty($user->telegram_id)) {
            $result = $this->telegramService->getTelegramUserInfo($user->telegram_id);
            if ($result->isSuccess()) {
                $telegramUserInfo = $result->getData();
            }
        }

        return Inertia::render('contact/index', [
            'contactInfo' => $contactInfo,
            'telegramUserInfo' => $telegramUserInfo,
            'userTelegramId' => $user->telegram_id ?? null,
        ]);
    }
}

