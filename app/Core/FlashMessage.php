<?php

namespace App\Core;

class FlashMessage
{

    public static function success(string $message): void
    {
        session()->flash('success', $message);
    }
    public static function error(string $message): void
    {
        session()->flash('error', $message);
    }
    public static function info(string $message): void
    {
        session()->flash('info', $message);
    }
    public static function warning(string $message): void
    {
        session()->flash('warning', $message);
    }
}
