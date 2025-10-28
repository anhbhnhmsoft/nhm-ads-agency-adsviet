<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::middleware(['guest:web'])->group(function () {
    Route::get('/login', [AuthController::class, 'loginScreen'])->name('login');
    Route::post('/login-username', [AuthController::class, 'handleLoginUsername'])->name('login_username');
    Route::get('/register', [AuthController::class, 'registerScreen'])->name('register');
});


Route::middleware(['auth:web'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('dashboard/index',[]);
    })->name('dashboard');
});

