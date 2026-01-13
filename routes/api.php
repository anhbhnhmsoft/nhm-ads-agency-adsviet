<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommonController;
use App\Http\Controllers\API\ConfigController;
use App\Http\Controllers\API\GoogleAdsController;
use App\Http\Controllers\API\MetaController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\TicketController;
use App\Http\Controllers\API\WalletController;
use App\Http\Middleware\VerifyTelegramIp;
use Illuminate\Support\Facades\Route;

Route::prefix('common')->group(function () {
    Route::prefix('telegram')->group(function () {
        Route::post('webhook', [CommonController::class, 'handleTelegramWebhook'])
            ->middleware([VerifyTelegramIp::class]); // Đảm bảo rằng request chỉ từ Telegram IP
        Route::get('config', [CommonController::class, 'getTelegramConfig']);
    });
});

Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-register', [AuthController::class, 'verifyRegister']);

    Route::get('telegram-callback', [AuthController::class, 'telegramCallback'])->name('api.auth.telegram.callback');
    Route::post('telegram-login', [AuthController::class, 'telegramLogin']);
    Route::post('telegram-register', [AuthController::class, 'registerTelegram']);

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-forgot-password', [AuthController::class, 'verifyForgotPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'getProfile']);
        // API change password
        Route::put('/change-password', [ProfileController::class, 'changePassword'])
            ->middleware('throttle:5,1');
    });

    Route::prefix('config')->group(function () {
        Route::get('postpay-min-balance', [ConfigController::class, 'getPostpayMinBalance']);
    });

    Route::prefix('service')->group(function () {
        Route::get('service-owner', [ServiceController::class, 'serviceOwner']);
        Route::get('package', [ServiceController::class, 'package']);
        Route::post('register-package', [ServiceController::class, 'registerServicePackage']);
        Route::get('dashboard', [ServiceController::class, 'dashboard']);
        Route::get('report', [ServiceController::class, 'report']);
        Route::get('report-insight', [ServiceController::class, 'reportInsight']);

    });

    Route::prefix('meta')->group(function () {
        Route::get('/{serviceUserId}/accounts', [MetaController::class, 'getAdsAccount']);
        Route::get('/{serviceUserId}/{accountId}/campaigns', [MetaController::class, 'getCampaigns']);
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign', [MetaController::class, 'detailCampaign']);
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign-insight', [MetaController::class, 'getCampaignInsights']);
    });

    Route::prefix('google-ads')->group(function () {
        Route::get('/{serviceUserId}/accounts', [GoogleAdsController::class, 'getAdsAccount']);
        Route::get('/{serviceUserId}/{accountId}/campaigns', [GoogleAdsController::class, 'getCampaigns']);
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign', [GoogleAdsController::class, 'detailCampaign']);
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign-insight', [GoogleAdsController::class, 'getCampaignInsights']);
    });

    Route::prefix('wallet')->group(function () {
        Route::get('me', [WalletController::class, 'me']);
        Route::get('transactions', [WalletController::class, 'transactions']);
        Route::post('deposit', [WalletController::class, 'deposit']) // API nạp tiền
        ->middleware('throttle:5,1'); // Giới hạn 5 lần mỗi phút
        Route::get('check-deposit', [WalletController::class, 'checkDeposit']);
        Route::post('change-password', [WalletController::class, 'changePassword'])->middleware('throttle:5,1');
        Route::post('withdraw', [WalletController::class, 'withdraw']) // API rút tiền
        ->middleware('throttle:5,1'); // Giới hạn 5 lần mỗi phút
        Route::post('campaign-budget-update', [WalletController::class, 'campaignBudgetUpdate'])
        ->middleware('throttle:5,1'); // Giới hạn 5 lần mỗi phút
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::get('/{id}', [TicketController::class, 'show']);
        Route::post('/', [TicketController::class, 'store']);
        Route::post('/{id}/message', [TicketController::class, 'addMessage']);
        Route::put('/{id}/status', [TicketController::class, 'updateStatus']);
        Route::get('/create-account', [TicketController::class, 'createAccount']);
        Route::post('/create-account', [TicketController::class, 'storeCreateAccount']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

});
