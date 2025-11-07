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
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-forgot-password', [AuthController::class, 'verifyForgotPassword']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'getProfile']);
    });
});
