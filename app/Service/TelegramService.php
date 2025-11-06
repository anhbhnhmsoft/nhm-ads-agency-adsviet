<?php

namespace App\Service;
use Telegram\Bot\Api;
class TelegramService
{
    public function __construct(protected Api $bot)
    {
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
