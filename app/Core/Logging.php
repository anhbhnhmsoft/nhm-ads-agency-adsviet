<?php

namespace App\Core;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class Logging
{
    /**
     * Ghi nhận action của user web hoặc IP khi có request
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function web(string $message, array $context = []): void
    {
        $ip = request()->ip();
        $context['ip'] = $ip;
        if (Auth::guard('web')->check()) {
            $context['user_id'] = Auth::guard('web')->id();
            $message = "User " . $context['user_id'] . ": " . $message;
        } else {
            $message = "IP " . $ip . ": " . $message;
        }
        Log::channel('action')->info($message, $context);
    }

    /**
     * Ghi nhận action của user api hoặc IP khi có request
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function api(string $message, array $context = []): void
    {
        $ip = request()->ip();
        $context['ip'] = $ip;
        if (Auth::guard('api')->check()) {
            $context['user_id'] = Auth::guard('api')->id();
            $message = "User " . $context['user_id'] . ": " . $message;
        } else {
            $message = "IP " . $ip . ": " . $message;
        }
        Log::channel('action')->info($message, $context);
    }

    /**
     * Ghi nhận error khi có exception hoặc lỗi khác
     * @param string $message
     * @param array $context
     * @param \Throwable|null $exception
     * @return void
     */
    public static function error(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        if ($exception){
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }
        Log::channel('error')->error($message, $context);
    }


    /**
     * Ghi nhận log khi chạy command
     * @param string $command - mỗi command sẽ có 1 file log riêng
     * @return LoggerInterface
     */
    public static function console(string $command): LoggerInterface
    {
        return Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs' . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command . DIRECTORY_SEPARATOR . 'command.log'),
            'level' => 'debug',
            'replace_placeholders' => true,
            'days' => 1
        ]);
    }

}
