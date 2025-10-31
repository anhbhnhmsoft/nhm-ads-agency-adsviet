<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



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
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('user')->group(function () {
       Route::get('/list-employee', [UserController::class, 'listEmployee'])->name('user_list_employee');
       Route::get('/create-employee', [UserController::class, 'createEmployeeScreen'])->name('user_create_employee');
    });
});

