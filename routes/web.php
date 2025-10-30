<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::middleware(['guest:web'])->group(function () {
    Route::get('/login', [AuthController::class, 'loginScreen'])->name('login');
    Route::get('/register', [AuthController::class, 'registerScreen'])->name('register');

    // xác thực
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'handleLoginUsername'])->name('auth_login');
        Route::post('/start', [AuthController::class, 'handleLoginTelegram'])->name('auth_telegram');
        Route::get('/register-new-user', [AuthController::class, 'registerNewUserScreen'])
            ->name('auth_register_new_user_screen');
        Route::post('/register-new-user', [AuthController::class, 'handleRegisterNewUser'])
            ->name('auth_register_new_user');

    });

});


Route::middleware(['auth:web'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('dashboard/index',[]);
    })->name('dashboard');
});

