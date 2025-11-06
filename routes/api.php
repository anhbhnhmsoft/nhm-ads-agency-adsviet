<?php


use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommonController;
use App\Http\Middleware\VerifyTelegramIp;
use Illuminate\Support\Facades\Route;

Route::prefix('common')->group(function () {
    Route::prefix('telegram')->group(function () {
        Route::post('webhook', [CommonController::class, 'handleTelegramWebhook'])
            ->middleware([VerifyTelegramIp::class]); // Đảm bảo rằng request chỉ từ Telegram IP

        Route::get('config', [CommonController::class, 'getTelegramConfig']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'LoginUsername'])
        ->name('api.auth.login');
    Route::get('telegram-callback', [AuthController::class, 'handleTelegramCallback'])
        ->name('api.auth.telegram.callback');
    Route::post('telegram-login', [AuthController::class, 'handleTelegramLogin']);
    Route::post('register', [AuthController::class, 'register']);

});
