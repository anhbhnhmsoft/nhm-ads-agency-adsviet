<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlatformSettingController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\EnsureUserIsActive;
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


Route::middleware(['auth:web', EnsureUserIsActive::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('user')->group(function () {
        Route::get('/list-employee', [UserController::class, 'listEmployee'])->name('user_list_employee');
        Route::get('/create-employee', [UserController::class, 'createEmployeeScreen'])->name('user_create_employee');
        Route::get('/employee/{id}/edit', [UserController::class, 'editEmployeeScreen'])->name('user_employee_edit');
        Route::post('/employee', [UserController::class, 'store'])->name('user_employee_store');
        Route::put('/employee/{id}', [UserController::class, 'update'])->name('user_employee_update');
        Route::delete('/employee/{id}', [UserController::class, 'destroy'])->name('user_employee_destroy');
        Route::post('/employee/{id}/toggle-disable', [UserController::class, 'toggleDisable'])->name('user_employee_toggle_disable');
        
        Route::get('/manager/{managerId}/employees', [UserController::class, 'getEmployeesByManager'])->name('user_get_employees_by_manager');
        Route::post('/employee/assign', [UserController::class, 'assignEmployee'])->name('user_assign_employee');
        Route::post('/employee/unassign', [UserController::class, 'unassignEmployee'])->name('user_unassign_employee');
    });

    Route::prefix('/customer')->group(function (){
        Route::get('/list', [UserController::class, 'listCustomer'])->name('user_list');
        Route::get('/{id}/edit', [UserController::class, 'editUserScreen'])->name('user_edit');
        Route::put('/{id}', [UserController::class, 'updateUser'])->name('user_update');
        Route::post('/{id}/toggle-disable', [UserController::class, 'userToggleDisable'])->name('user_toggle_disable');
        Route::delete('/{id}', [UserController::class, 'destroyUser'])->name('user_destroy');
    });

    Route::prefix('/platform-settings')->group(function (){
        Route::get('/', [PlatformSettingController::class, 'index'])->name('platform_settings_index');
        Route::post('/', [PlatformSettingController::class, 'store'])->name('platform_settings_store');
        Route::put('/{id}', [PlatformSettingController::class, 'update'])->name('platform_settings_update');
        Route::post('/{id}/toggle', [PlatformSettingController::class, 'toggle'])->name('platform_settings_toggle');
    });

    Route::prefix('/wallets')->group(function(){
        Route::post('/{userId}/create', [WalletController::class, 'create'])->name('wallet_create');
        Route::post('/{userId}/top-up', [WalletController::class, 'topUp'])->name('wallet_top_up');
        Route::post('/{userId}/withdraw', [WalletController::class, 'withdraw'])->name('wallet_withdraw');
        Route::post('/{userId}/lock', [WalletController::class, 'lock'])->name('wallet_lock');
        Route::post('/{userId}/unlock', [WalletController::class, 'unlock'])->name('wallet_unlock');
        Route::post('/{userId}/reset-password', [WalletController::class, 'resetPassword'])->name('wallet_reset_password');
    });
});
